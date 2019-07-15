<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
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

    public function __construct(int $lifetime, RefreshTokenRepository $refreshTokenRepository)
    {
        $this->lifetime = $lifetime;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function beforeAccessTokenIssuance(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType, callable $next): GrantTypeData
    {
        return $next($request, $grantTypeData, $grantType);
    }

    public function afterAccessTokenIssuance(Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken, callable $next): array
    {
        $result = $next($client, $resourceOwner, $accessToken);
        $scope = $accessToken->getParameter()->has('scope') ? explode(' ', $accessToken->getParameter()->get('scope')) : [];
        if (\in_array('offline_access', $scope, true)) {
            $expiresAt = new \DateTimeImmutable(\Safe\sprintf('now +%u seconds', $this->lifetime));
            $refreshToken = $this->refreshTokenRepository->create(
                $accessToken->getClientId(),
                $accessToken->getResourceOwnerId(),
                $expiresAt,
                $accessToken->getParameter(),
                $accessToken->getMetadata(),
                null
            );
            $refreshToken->addAccessToken($accessToken->getId());
            $this->refreshTokenRepository->save($refreshToken);
            $result['refresh_token'] = $refreshToken->getId()->getValue();
        }

        return $result;
    }
}
