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

namespace OAuth2Framework\Component\Server\RefreshTokenGrant;

use OAuth2Framework\Component\Server\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Server\TokenEndpoint\Extension\TokenEndpointExtension;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class RefreshTokenEndpointExtension implements TokenEndpointExtension
{
    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;

    /**
     * RefreshTokenEndpointExtension constructor.
     *
     * @param RefreshTokenRepository $refreshTokenRepository
     */
    public function __construct(RefreshTokenRepository $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAccessTokenIssuance(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType, callable $next): GrantTypeData
    {
        $grantTypeData = $next($request, $grantTypeData, $grantType);
        $scope = explode(' ', $grantTypeData->hasParameter('scope') ? $grantTypeData->getParameter('scope') : '');
        if (in_array('offline_access', $scope) && null !== $this->refreshTokenRepository) {
            $refreshToken = $this->refreshTokenRepository->create(
                $grantTypeData->getResourceOwnerId(),
                $grantTypeData->getClient()->getPublicId(),
                $grantTypeData->getParameters(),
                $grantTypeData->getMetadatas(),
                null
            );
            $grantTypeData = $grantTypeData->withParameter('refresh_token', $refreshToken->getTokenId()->getValue());
        }

        return $grantTypeData;
    }

    /**
     * {@inheritdoc}
     */
    public function afterAccessTokenIssuance(Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken, callable $next): array
    {
        if ($accessToken->hasParameter('refresh_token')) {
            $refreshTokenId = RefreshTokenId::create($accessToken->getParameter('refresh_token'));
            $refreshToken = $this->refreshTokenRepository->find($refreshTokenId);
            if (null !== $refreshToken) {
                $refreshToken = $refreshToken->addAccessToken($accessToken->getTokenId());
                $this->refreshTokenRepository->save($refreshToken);
            }
        }

        return $next($client, $resourceOwner, $accessToken);
    }
}
