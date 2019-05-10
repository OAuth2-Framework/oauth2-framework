<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\TokenEndpoint;

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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TokenEndpoint implements MiddlewareInterface
{
    /**
     * @var TokenEndpointExtensionManager
     */
    private $tokenEndpointExtensionManager;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var UserAccountRepository|null
     */
    private $userAccountRepository;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * @var int
     */
    private $accessTokenLifetime;

    public function __construct(ClientRepository $clientRepository, ?UserAccountRepository $userAccountRepository, TokenEndpointExtensionManager $tokenEndpointExtensionManager, ResponseFactoryInterface $responseFactory, AccessTokenRepository $accessTokenRepository, int $accessLifetime)
    {
        $this->clientRepository = $clientRepository;
        $this->userAccountRepository = $userAccountRepository;
        $this->tokenEndpointExtensionManager = $tokenEndpointExtensionManager;
        $this->responseFactory = $responseFactory;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->accessTokenLifetime = $accessLifetime;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // We prepare the Grant Type Data.
        // The client may be null (authenticated by other means).
        $grantTypeData = new GrantTypeData($request->getAttribute('client'));

        // We retrieve the Grant Type.
        // This middleware must be behind the GrantTypeMiddleware
        $grantType = $request->getAttribute('grant_type');
        if (!$grantType instanceof GrantType) {
            throw new OAuth2Error(500, OAuth2Error::ERROR_INTERNAL, null);
        }

        // We check that the request has all parameters needed for the selected grant type
        $grantType->checkRequest($request);

        // The grant type prepare the token response
        // The grant type data should be updated accordingly
        $grantType->prepareResponse($request, $grantTypeData);

        // At this stage, the client should be authenticated
        // If not, we stop the authorization grant
        if (!$grantTypeData->hasClient() || $grantTypeData->getClient()->isDeleted()) {
            throw new OAuth2Error(401, OAuth2Error::ERROR_INVALID_CLIENT, 'Client authentication failed.');
        }

        // We check the client is allowed to use the selected grant type
        if (!$grantTypeData->getClient()->isGrantTypeAllowed($grantType->name())) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_UNAUTHORIZED_CLIENT, \Safe\sprintf('The grant type "%s" is unauthorized for this client.', $grantType->name()));
        }

        // We populate the token type parameters
        $this->updateWithTokenTypeParameters($request, $grantTypeData);

        // We call for extensions prior to the Access Token issuance
        $grantTypeData = $this->tokenEndpointExtensionManager->handleBeforeAccessTokenIssuance($request, $grantTypeData, $grantType);

        // We grant the client
        $grantType->grant($request, $grantTypeData);

        // Everything is fine so we can issue the access token
        $accessToken = $this->issueAccessToken($grantTypeData);
        $resourceOwner = $this->getResourceOwner($grantTypeData->getResourceOwnerId());

        // We call for extensions after to the Access Token issuance
        $data = $this->tokenEndpointExtensionManager->handleAfterAccessTokenIssuance($grantTypeData->getClient(), $resourceOwner, $accessToken);

        return $this->createResponse($data);
    }

    private function createResponse(array $data): ResponseInterface
    {
        $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        $response = $this->responseFactory->createResponse(200);
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        $response->getBody()->write(\Safe\json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response;
    }

    private function issueAccessToken(GrantTypeData $grantTypeData): AccessToken
    {
        $accessToken = $this->accessTokenRepository->create(
            $grantTypeData->getClient()->getClientId(),
            $grantTypeData->getResourceOwnerId(),
            new \DateTimeImmutable(\Safe\sprintf('now +%d seconds', $this->accessTokenLifetime)),
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
        if (null === $resourceOwner && null !== $this->userAccountRepository) {
            $resourceOwner = $this->userAccountRepository->find(new UserAccountId($resourceOwnerId->getValue()));
        }

        if (null === $resourceOwner) {
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
            $grantTypeData->getParameter()->set($k, $v);
        }
    }

    private function isGrantTypeAllowedForTheClient(Client $client, string $grant_type): bool
    {
        $grant_types = $client->has('grant_types') ? $client->get('grant_types') : [];
        if (!\is_array($grant_types)) {
            throw new \InvalidArgumentException('The metadata "grant_types" must be an array.');
        }

        return \in_array($grant_type, $grant_types, true);
    }
}
