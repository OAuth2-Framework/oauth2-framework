<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\TokenEndpoint;

use Http\Message\ResponseFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\TokenEndpoint\Processor\ProcessorManager;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenRepository;
use OAuth2Framework\Component\Server\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TokenEndpoint implements MiddlewareInterface
{
    /**
     * @var TokenEndpointExtensionManager
     */
    private $tokenEndpointExtensionManager;

    /**
     * @var ProcessorManager
     */
    private $processorManager;

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
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;

    /**
     * TokenEndpoint constructor.
     *
     * @param ProcessorManager              $processorManager
     * @param ClientRepository              $clientRepository
     * @param UserAccountRepository         $userAccountRepository
     * @param TokenEndpointExtensionManager $tokenEndpointExtensionManager
     * @param ResponseFactory               $responseFactory
     * @param AccessTokenRepository         $accessTokenRepository
     * @param RefreshTokenRepository        $refreshTokenRepository
     */
    public function __construct(ProcessorManager $processorManager, ClientRepository $clientRepository, UserAccountRepository $userAccountRepository, TokenEndpointExtensionManager $tokenEndpointExtensionManager, ResponseFactory $responseFactory, AccessTokenRepository $accessTokenRepository, RefreshTokenRepository $refreshTokenRepository)
    {
        $this->processorManager = $processorManager;
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
        $grantTypeData = GrantTypeData::create($request->getAttribute('client'));

        /** @var $grantType GrantType From the dedicated middleware */
        $grantType = $request->getAttribute('grant_type');
        $grantType->checkTokenRequest($request);
        $grantTypeData = $grantType->prepareTokenResponse($request, $grantTypeData);
        if (null === $grantTypeData->getClient() || $grantTypeData->getClient()->isDeleted()) {
            throw new OAuth2Exception(401, OAuth2Exception::ERROR_INVALID_CLIENT, 'Client authentication failed.');
        }

        // This occurs now because the client may be found during the preparation process
        $this->checkGrantType($grantTypeData->getClient(), $grantType->getGrantType());

        $grantTypeData = $this->processorManager->handle($request, $grantTypeData, $grantType);

        $accessToken = $this->issueAccessToken($grantTypeData);
        $data = $this->tokenEndpointExtensionManager->process($grantTypeData->getClient(), $this->getResourceOwner($grantTypeData->getResourceOwnerId()), $accessToken);

        return $this->createResponse($data);
    }

    /**
     * @param array $data
     *
     * @return ResponseInterface
     */
    private function createResponse(array $data): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }

    /**
     * @param Client $client
     * @param string $grantType
     *
     * @throws OAuth2Exception
     */
    private function checkGrantType(Client $client, string $grantType)
    {
        if (!$client->isGrantTypeAllowed($grantType)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_UNAUTHORIZED_CLIENT, sprintf('The grant type "%s" is unauthorized for this client.', $grantType));
        }
    }

    /**
     * @param GrantTypeData $grantTypeData
     *
     * @return AccessToken
     */
    private function issueAccessToken(GrantTypeData $grantTypeData): AccessToken
    {
        if ($grantTypeData->hasRefreshToken()) {
            $refreshToken = $this->refreshTokenRepository->create(
                $grantTypeData->getResourceOwnerId(),
                $grantTypeData->getClient()->getPublicId(),
                $grantTypeData->getParameters(),
                $grantTypeData->getMetadatas(),
                $grantTypeData->getScopes(),
                null,
                null
            );
        } else {
            $refreshToken = null;
        }

        $accessToken = $this->accessTokenRepository->create(
            $grantTypeData->getResourceOwnerId(),
            $grantTypeData->getClient()->getPublicId(),
            $grantTypeData->getParameters(),
            $grantTypeData->getMetadatas(),
            $grantTypeData->getScopes(),
            null === $refreshToken ? null : $refreshToken->getTokenId(),
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
}
