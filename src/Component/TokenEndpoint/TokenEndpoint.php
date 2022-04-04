<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenEndpoint;

use DateTimeImmutable;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtensionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TokenEndpoint implements MiddlewareInterface
{
    public function __construct(
        private ClientRepository $clientRepository,
        private ?UserAccountRepository $userAccountRepository,
        private TokenEndpointExtensionManager $tokenEndpointExtensionManager,
        private AccessTokenRepository $accessTokenRepository,
        private int $accessTokenLifetime
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // We prepare the Grant Type Data.
        // The client may be null (authenticated by other means).
        $grantTypeData = GrantTypeData::create($request->getAttribute('client'));

        // We retrieve the Grant Type.
        // This middleware must be behind the GrantTypeMiddleware
        $grantType = $request->getAttribute('grant_type');
        if (! $grantType instanceof GrantType) {
            throw new OAuth2Error(500, OAuth2Error::ERROR_INTERNAL, null);
        }

        // We check that the request has all parameters needed for the selected grant type
        $grantType->checkRequest($request);

        // The grant type prepare the token response
        // The grant type data should be updated accordingly
        $grantType->prepareResponse($request, $grantTypeData);

        // At this stage, the client should be authenticated
        // If not, we stop the authorization grant
        if (! $grantTypeData->hasClient() || $grantTypeData->getClient()->isDeleted()) {
            throw new OAuth2Error(401, OAuth2Error::ERROR_INVALID_CLIENT, 'Client authentication failed.');
        }

        // We check the client is allowed to use the selected grant type
        if (! $grantTypeData->getClient()->isGrantTypeAllowed($grantType->name())) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_UNAUTHORIZED_CLIENT, sprintf(
                'The grant type "%s" is unauthorized for this client.',
                $grantType->name()
            ));
        }

        // We populate the token type parameters
        $this->updateWithTokenTypeParameters($request, $grantTypeData);

        // We call for extensions prior to the Access Token issuance
        $grantTypeData = $this->tokenEndpointExtensionManager->handleBeforeAccessTokenIssuance(
            $request,
            $grantTypeData,
            $grantType
        );

        // We grant the client
        $grantType->grant($request, $grantTypeData);

        // Everything is fine so we can issue the access token
        $accessToken = $this->issueAccessToken($grantTypeData);
        $resourceOwner = $this->getResourceOwner($grantTypeData->getResourceOwnerId());

        // We call for extensions after to the Access Token issuance
        $data = $this->tokenEndpointExtensionManager->handleAfterAccessTokenIssuance(
            $grantTypeData->getClient(),
            $resourceOwner,
            $accessToken
        );

        $response = $handler->handle($request);

        return $this->createResponse($data, $response);
    }

    private function createResponse(array $data, ResponseInterface $response): ResponseInterface
    {
        $headers = [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private',
            'Pragma' => 'no-cache',
        ];
        $response = $response->withStatus(200);
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        $response->getBody()
            ->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ;

        return $response;
    }

    private function issueAccessToken(GrantTypeData $grantTypeData): AccessToken
    {
        $accessToken = $this->accessTokenRepository->create(
            $grantTypeData->getClient()
                ->getClientId(),
            $grantTypeData->getResourceOwnerId(),
            new DateTimeImmutable(sprintf('now +%d seconds', $this->accessTokenLifetime)),
            $grantTypeData->getParameter(),
            $grantTypeData->getMetadata(),
            null
        );
        $this->accessTokenRepository->save($accessToken);

        return $accessToken;
    }

    private function getResourceOwner(ResourceOwnerId $resourceOwnerId): ResourceOwner
    {
        $resourceOwner = $this->clientRepository->find(new ClientId($resourceOwnerId->getValue()));
        if ($resourceOwner === null && $this->userAccountRepository !== null) {
            $resourceOwner = $this->userAccountRepository->find(new UserAccountId($resourceOwnerId->getValue()));
        }

        if ($resourceOwner === null) {
            throw OAuth2Error::invalidRequest('Unable to find the associated resource owner.');
        }

        return $resourceOwner;
    }

    private function updateWithTokenTypeParameters(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        /** @var TokenType $tokenType */
        $tokenType = $request->getAttribute('token_type');

        $info = $tokenType->getAdditionalInformation();
        $info['token_type'] = $tokenType->name();
        foreach ($info as $k => $v) {
            $grantTypeData->getParameter()
                ->set($k, $v)
            ;
        }
    }
}
