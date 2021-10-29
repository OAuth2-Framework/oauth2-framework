<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use function array_key_exists;
use function count;
use InvalidArgumentException;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class AuthorizationCodeGrantType implements GrantType
{
    public function __construct(
        private AuthorizationCodeRepository $authorizationCodeRepository,
        private PKCEMethodManager $pkceMethodManager
    ) {
    }

    public function associatedResponseTypes(): array
    {
        return ['code'];
    }

    public function name(): string
    {
        return 'authorization_code';
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $requiredParameters = ['code', 'redirect_uri'];

        $diff = array_diff($requiredParameters, array_keys($parameters));
        if (count($diff) !== 0) {
            throw OAuth2Error::invalidRequest(sprintf('Missing grant type parameter(s): %s.', implode(', ', $diff)));
        }
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $authorizationCode = $this->getAuthorizationCode($parameters['code']);

        if ($authorizationCode->isUsed() === true || $authorizationCode->isRevoked() === true) {
            throw OAuth2Error::invalidGrant('The parameter "code" is invalid.');
        }

        $this->checkClient($grantTypeData->getClient(), $parameters);
        $this->checkAuthorizationCode($authorizationCode, $grantTypeData->getClient());
        $this->checkPKCE($authorizationCode, $parameters);

        $redirectUri = $parameters['redirect_uri'];
        $this->checkRedirectUri($authorizationCode, $redirectUri);

        foreach ($authorizationCode->getParameter() as $key => $parameter) {
            $grantTypeData->getParameter()
                ->set($key, $parameter)
            ;
        }
        foreach ($authorizationCode->getMetadata() as $key => $parameter) {
            $grantTypeData->getMetadata()
                ->set($key, $parameter)
            ;
        }

        $grantTypeData->getMetadata()
            ->set('redirect_uri', $redirectUri)
        ;
        $grantTypeData->getMetadata()
            ->set('authorization_code_id', $authorizationCode->getId()->getValue())
        ;
        $grantTypeData->setResourceOwnerId($authorizationCode->getUserAccountId());
        $authorizationCode->markAsUsed();
        $this->authorizationCodeRepository->save($authorizationCode);
    }

    private function getAuthorizationCode(string $code): AuthorizationCode
    {
        $authorizationCode = $this->authorizationCodeRepository->find(new AuthorizationCodeId($code));

        if (! $authorizationCode instanceof AuthorizationCode) {
            throw OAuth2Error::invalidGrant('The parameter "code" is invalid.');
        }

        return $authorizationCode;
    }

    private function checkClient(Client $client, array $parameters): void
    {
        if ($client->isPublic() === true) {
            if (! array_key_exists('client_id', $parameters) || $client->getPublicId()
                ->getValue() !== $parameters['client_id']) {
                throw OAuth2Error::invalidRequest(
                    'The "client_id" parameter is required for non-confidential clients.'
                );
            }
        }
    }

    private function checkPKCE(AuthorizationCode $authorizationCode, array $parameters): void
    {
        $params = $authorizationCode->getQueryParameters();
        if (! array_key_exists('code_challenge', $params)) {
            return;
        }

        $codeChallenge = $params['code_challenge'];
        $codeChallengeMethod = array_key_exists(
            'code_challenge_method',
            $params
        ) ? $params['code_challenge_method'] : 'plain';

        try {
            if (! array_key_exists('code_verifier', $parameters)) {
                throw OAuth2Error::invalidGrant('The parameter "code_verifier" is missing or invalid.');
            }
            $code_verifier = $parameters['code_verifier'];
            $method = $this->pkceMethodManager->get($codeChallengeMethod);
        } catch (InvalidArgumentException $e) {
            throw OAuth2Error::invalidRequest($e->getMessage(), [], $e);
        }

        if ($method->isChallengeVerified($code_verifier, $codeChallenge) === false) {
            throw OAuth2Error::invalidGrant('The parameter "code_verifier" is invalid or invalid.');
        }
    }

    private function checkRedirectUri(AuthorizationCode $authorizationCode, string $redirectUri): void
    {
        if ($redirectUri !== $authorizationCode->getRedirectUri()) {
            throw OAuth2Error::invalidRequest('The parameter "redirect_uri" is invalid.');
        }
    }

    private function checkAuthorizationCode(AuthorizationCode $authorizationCode, Client $client): void
    {
        if ($client->getPublicId()->getValue() !== $authorizationCode->getClientId()->getValue()) {
            throw OAuth2Error::invalidGrant('The parameter "code" is invalid.');
        }

        if ($authorizationCode->hasExpired()) {
            throw OAuth2Error::invalidGrant('The authorization code expired.');
        }
    }
}
