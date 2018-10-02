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
    private $lifetime;
    private $refreshTokenRepository;
    private $refreshTokenIdGenerator;

    public function __construct(int $lifetime, RefreshTokenRepository $refreshTokenRepository, RefreshTokenIdGenerator $refreshTokenIdGenerator)
    {
        $this->lifetime = $lifetime;
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->refreshTokenIdGenerator = $refreshTokenIdGenerator;
    }

    public function beforeAccessTokenIssuance(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType, callable $next): GrantTypeData
    {
        return $next($request, $grantTypeData, $grantType);
    }

    public function afterAccessTokenIssuance(Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken, callable $next): array
    {
        $result = $next($client, $resourceOwner, $accessToken);
        $scope = $accessToken->getParameter()->has('scope') ? \explode(' ', $accessToken->getParameter()->get('scope')) : [];
        if (\in_array('offline_access', $scope, true)) {
            $expiresAt = new \DateTimeImmutable(\Safe\sprintf('now +%u seconds', $this->lifetime));
            $refreshTokenId = $this->refreshTokenIdGenerator->createRefreshTokenId();
            $refreshToken = new RefreshToken(
                $refreshTokenId,
                $accessToken->getClientId(),
                $accessToken->getResourceOwnerId(),
                $accessToken->getParameter(),
                $accessToken->getMetadata(),
                $expiresAt,
                null);
            $refreshToken->addAccessToken($accessToken->getTokenId());
            $this->refreshTokenRepository->save($refreshToken);
            $result['refresh_token'] = $refreshToken->getTokenId()->getValue();
        }

        return $result;
    }
}
