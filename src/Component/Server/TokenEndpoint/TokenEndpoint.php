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

namespace OAuth2Framework\Component\Server\TokenEndpoint;

use Http\Message\ResponseFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenRepository;
use OAuth2Framework\Component\Server\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\TokenEndpoint\Extension\TokenEndpointExtensionManager;
use OAuth2Framework\Component\Server\TokenType\TokenType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TokenEndpoint implements MiddlewareInterface
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
     * @var UserAccountRepository
     */
    private $userAccountRepository;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * @var null|RefreshTokenRepository
     */
    private $refreshTokenRepository;

    /**
     * TokenEndpoint constructor.
     *
     * @param ClientRepository              $clientRepository
     * @param UserAccountRepository         $userAccountRepository
     * @param TokenEndpointExtensionManager $tokenEndpointExtensionManager
     * @param ResponseFactory               $responseFactory
     * @param AccessTokenRepository         $accessTokenRepository
     * @param RefreshTokenRepository|null   $refreshTokenRepository
     */
    public function __construct(ClientRepository $clientRepository, UserAccountRepository $userAccountRepository, TokenEndpointExtensionManager $tokenEndpointExtensionManager, ResponseFactory $responseFactory, AccessTokenRepository $accessTokenRepository, ?RefreshTokenRepository $refreshTokenRepository)
    {
        $this->clientRepository = $clientRepository;
        $this->userAccountRepository = $userAccountRepository;
        $this->tokenEndpointExtensionManager = $tokenEndpointExtensionManager;
        $this->responseFactory = $responseFactory;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // We prepare the Grant Type Data.
        // The client may be null (authenticated by other means).
        $grantTypeData = GrantTypeData::create($request->getAttribute('client'));

        // We retrieve the Grant Type.
        // This middleware must be behind the GrantTypeMiddleware
        $grantType = $request->getAttribute('grant_type');
        if (!$grantType instanceof GrantType) {
            throw new OAuth2Exception(500, OAuth2Exception::ERROR_INTERNAL, null);
        }

        // We check that the request has all parameters needed for the selected grant type
        $grantType->checkTokenRequest($request);

        // The grant type prepare the token response
        // The grant type data should be updated accordingly
        $grantTypeData = $grantType->prepareTokenResponse($request, $grantTypeData);

        // At this stage, the client should be authenticated
        // If not, we stop the authorization grant
        if (null === $grantTypeData->getClient() || $grantTypeData->getClient()->isDeleted()) {
            throw new OAuth2Exception(401, OAuth2Exception::ERROR_INVALID_CLIENT, 'Client authentication failed.');
        }

        // This occurs now because the client may be found during the preparation process
        if (!$grantTypeData->getClient()->isGrantTypeAllowed($grantType->getGrantType())) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_UNAUTHORIZED_CLIENT, sprintf('The grant type "%s" is unauthorized for this client.', $grantType));
        }

        // We populate the token type parameters
        $grantTypeData = $this->updateWithTokenTypeParameters($request, $grantTypeData);

        // Should be token extension pre-processing
        $grantTypeData = $this->tokenEndpointExtensionManager->handleBeforeAccessTokenIssuance($request, $grantTypeData, $grantType);

        // We grant the client
        $grantTypeData = $grantType->grant($request, $grantTypeData);

        // Everything is fine so we can issue the access token
        $accessToken = $this->issueAccessToken($grantTypeData);
        $resourceOwner = $this->getResourceOwner($grantTypeData->getResourceOwnerId());
        $data = $this->tokenEndpointExtensionManager->handleAfterAccessTokenIssuance($grantTypeData->getClient(), $resourceOwner, $accessToken);

        return $this->createResponse($data);
    }

    /**
     * @param array $data
     *
     * @return ResponseInterface
     */
    private function createResponse(array $data): ResponseInterface
    {
        $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        $response = $this->responseFactory->createResponse(200, null, $headers);
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response;
    }

    /**
     * @param GrantTypeData $grantTypeData
     *
     * @return AccessToken
     */
    private function issueAccessToken(GrantTypeData $grantTypeData): AccessToken
    {
        $parameters = $grantTypeData->getParameters();
        //FIXME
        /*if (in_array('offline_access', $grantTypeData->getScopes()) && null !== $this->refreshTokenRepository) {
            $refreshToken = $this->refreshTokenRepository->create(
                $grantTypeData->getClient()->getPublicId(),
                $grantTypeData->getParameters(),
                $grantTypeData->getMetadatas(),
                null,
                null
            );
            $parameters = $parameters->with('refresh_token', $refreshToken->getTokenId());
        } else {*/
        $refreshToken = null;
        //}

        $accessToken = $this->accessTokenRepository->create(
            $grantTypeData->getResourceOwnerId(),
            $grantTypeData->getClient()->getPublicId(),
            $parameters,
            $grantTypeData->getMetadatas(),
            null,
            null
        );

        if (null !== $refreshToken) {
            $refreshToken = $refreshToken->addAccessToken($accessToken->getTokenId());
            $this->refreshTokenRepository->save($refreshToken);
        }
        $this->accessTokenRepository->save($accessToken);

        return $accessToken;
    }

    /**
     * @param ResourceOwnerId $resourceOwnerId
     *
     * @throws OAuth2Exception
     *
     * @return ResourceOwner
     */
    private function getResourceOwner(ResourceOwnerId $resourceOwnerId): ResourceOwner
    {
        $resourceOwner = $this->clientRepository->find(ClientId::create($resourceOwnerId->getValue()));
        if (null === $resourceOwner) {
            $resourceOwner = $this->userAccountRepository->find(UserAccountId::create($resourceOwnerId->getValue()));
        }

        if (null === $resourceOwner) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, 'Unable to find the associated resource owner.');
        }

        return $resourceOwner;
    }

    /**
     * @param ServerRequestInterface $request
     * @param GrantTypeData          $grantTypeData
     *
     * @return GrantTypeData
     *
     * @throws OAuth2Exception
     */
    private function updateWithTokenTypeParameters(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        /** @var TokenType $tokenType */
        $tokenType = $request->getAttribute('token_type');
        if (!$grantTypeData->getClient()->isTokenTypeAllowed($tokenType->name())) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, sprintf('The token type "%s" is not allowed for the client.', $tokenType->name()));
        }

        $info = $tokenType->getInformation();
        foreach ($info as $k => $v) {
            $grantTypeData = $grantTypeData->withParameter($k, $v);
        }

        return $grantTypeData;
    }
}
