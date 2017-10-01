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

namespace OAuth2Framework\Component\Server\Endpoint\Token;

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\Endpoint\Token\Processor\ProcessorManager;
use OAuth2Framework\Component\Server\GrantType\GrantTypeInterface;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\Client\ClientRepositoryInterface;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
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
     * @var ClientRepositoryInterface
     */
    private $clientRepository;

    /**
     * @var UserAccountRepositoryInterface
     */
    private $userAccountRepository;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var AccessTokenRepositoryInterface
     */
    private $accessTokenRepository;

    /**
     * @var RefreshTokenRepositoryInterface
     */
    private $refreshTokenRepository;

    /**
     * TokenEndpoint constructor.
     *
     * @param ProcessorManager                $processorManager
     * @param ClientRepositoryInterface       $clientRepository
     * @param UserAccountRepositoryInterface  $userAccountRepository
     * @param TokenEndpointExtensionManager   $tokenEndpointExtensionManager
     * @param ResponseFactoryInterface        $responseFactory
     * @param AccessTokenRepositoryInterface  $accessTokenRepository
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     */
    public function __construct(ProcessorManager $processorManager, ClientRepositoryInterface $clientRepository, UserAccountRepositoryInterface $userAccountRepository, TokenEndpointExtensionManager $tokenEndpointExtensionManager, ResponseFactoryInterface $responseFactory, AccessTokenRepositoryInterface $accessTokenRepository, RefreshTokenRepositoryInterface $refreshTokenRepository)
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
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler)
    {
        $grantTypeData = GrantTypeData::create($request->getAttribute('client'));

        /** @var $grantType GrantTypeInterface From the dedicated middleware */
        $grantType = $request->getAttribute('grant_type');
        $grantType->checkTokenRequest($request);
        $grantTypeData = $grantType->prepareTokenResponse($request, $grantTypeData);
        if (null === $grantTypeData->getClient() || $grantTypeData->getClient()->isDeleted()) {
            throw new OAuth2Exception(401, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_CLIENT, 'error_description' => 'Client authentication failed.']);
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
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_UNAUTHORIZED_CLIENT, 'error_description' => sprintf('The grant type \'%s\' is unauthorized for this client.', $grantType)]);
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
     * @return ResourceOwnerInterface
     */
    private function getResourceOwner(ResourceOwnerId $resourceOwnerId): ResourceOwnerInterface
    {
        $resourceOwner = $this->clientRepository->find(ClientId::create($resourceOwnerId->getValue()));
        if (null === $resourceOwner) {
            $resourceOwner = $this->userAccountRepository->findUserAccount(UserAccountId::create($resourceOwnerId->getValue()));
        }

        if (null === $resourceOwner) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => 'Unable to find the associated resource owner.']);
        }

        return $resourceOwner;
    }
}
