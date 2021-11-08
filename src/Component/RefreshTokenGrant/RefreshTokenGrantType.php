<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\RefreshTokenGrant;

use Assert\Assertion;
use function count;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class RefreshTokenGrantType implements GrantType
{
    public function __construct(
        private RefreshTokenRepository $refreshTokenRepository
    ) {
    }

    public static function create(RefreshTokenRepository $refreshTokenRepository): self
    {
        return new self($refreshTokenRepository);
    }

    public function associatedResponseTypes(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'refresh_token';
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $requiredParameters = ['refresh_token'];

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
        $refreshToken = $parameters['refresh_token'];
        $token = $this->refreshTokenRepository->find(RefreshTokenId::create($refreshToken));

        if ($token === null) {
            throw OAuth2Error::invalidGrant('The parameter "refresh_token" is invalid.');
        }

        $client = $request->getAttribute('client');
        $this->checkRefreshToken($token, $client);

        $grantTypeData->setResourceOwnerId($token->getResourceOwnerId());
        foreach ($token->getMetadata() as $k => $v) {
            Assertion::string($k, 'Invalid key');
            $grantTypeData->getMetadata()
                ->set($k, $v)
            ;
        }
        foreach ($token->getParameter() as $k => $v) {
            Assertion::string($k, 'Invalid key');
            $grantTypeData->getParameter()
                ->set($k, $v)
            ;
        }
    }

    private function checkRefreshToken(RefreshToken $token, Client $client): void
    {
        if ($token->isRevoked() === true || $client->getPublicId()->getValue() !== $token->getClientId()->getValue()) {
            throw OAuth2Error::invalidGrant('The parameter "refresh_token" is invalid.');
        }

        if ($token->hasExpired()) {
            throw OAuth2Error::invalidGrant('The refresh token expired.');
        }
    }
}
