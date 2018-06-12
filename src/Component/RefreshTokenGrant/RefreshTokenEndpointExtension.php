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

namespace OAuth2Framework\Component\RefreshTokenGrant;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtension;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class RefreshTokenEndpointExtension implements TokenEndpointExtension
{
    /**
     * @var int
     */
    private $lifetime;

    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;

    /**
     * @var RefreshTokenIdGenerator
     */
    private $refreshTokenIdGenerator;

    /**
     * RefreshTokenEndpointExtension constructor.
     *
     * @param int                     $lifetime
     * @param RefreshTokenRepository  $refreshTokenRepository
     * @param RefreshTokenIdGenerator $refreshTokenIdGenerator
     */
    public function __construct(int $lifetime, RefreshTokenRepository $refreshTokenRepository, RefreshTokenIdGenerator $refreshTokenIdGenerator)
    {
        $this->lifetime = $lifetime;
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->refreshTokenIdGenerator = $refreshTokenIdGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAccessTokenIssuance(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType, callable $next): GrantTypeData
    {
        $grantTypeData = $next($request, $grantTypeData, $grantType);
        $scope = explode(' ', $grantTypeData->getParameter()->has('scope') ? $grantTypeData->getParameter()->get('scope') : '');
        if (in_array('offline_access', $scope)) {
            $expiresAt = new \DateTimeImmutable(sprintf('now +%u seconds', $this->lifetime));
            $refreshTokenId = $this->refreshTokenIdGenerator->createRefreshTokenId();
            $refreshToken = RefreshToken::createEmpty();
            $refreshToken = $refreshToken->create(
                $refreshTokenId,
                $grantTypeData->getResourceOwnerId(),
                $grantTypeData->getClient()->getPublicId(),
                $grantTypeData->getParameters(),
                $grantTypeData->getMetadatas(), $expiresAt,
                null);
            $this->refreshTokenRepository->save($refreshToken);
            $grantTypeData->withParameter('refresh_token', $refreshToken->getRefreshTokenId()->getValue());
        }

        return $grantTypeData;
    }

    /**
     * {@inheritdoc}
     */
    public function afterAccessTokenIssuance(Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken, callable $next): array
    {
        $result = $next($client, $resourceOwner, $accessToken);
        if ($accessToken->hasParameter('refresh_token')) {
            $refreshTokenId = RefreshTokenId::create($accessToken->getParameter('refresh_token'));
            $refreshToken = $this->refreshTokenRepository->find($refreshTokenId);
            if (null !== $refreshToken) {
                $refreshToken = $refreshToken->addAccessToken($accessToken->getAccessTokenId());
                $this->refreshTokenRepository->save($refreshToken);
                $result['refresh_token'] = $refreshToken->getRefreshTokenId()->getValue();
            }
        }

        return $result;
    }
}
