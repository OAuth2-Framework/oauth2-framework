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

use Base64Url\Base64Url;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtension;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

class RefreshTokenEndpointExtension implements TokenEndpointExtension
{
    /**
     * @var int
     */
    private $lifetime;

    /**
     * @var int
     */
    private $minLength;

    /**
     * @var int
     */
    private $maxLength;

    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepository;

    /**
     * RefreshTokenEndpointExtension constructor.
     *
     * @param int                    $minLength
     * @param int                    $maxLength
     * @param int                    $lifetime
     * @param RefreshTokenRepository $refreshTokenRepository
     */
    public function __construct(int $minLength, int $maxLength, int $lifetime, RefreshTokenRepository $refreshTokenRepository)
    {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->lifetime = $lifetime;
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
            $expiresAt = new \DateTimeImmutable(sprintf('now +%u seconds', $this->lifetime));
            $length = random_int($this->minLength, $this->maxLength);
            $refreshTokenId = RefreshTokenId::create(Base64Url::encode(random_bytes($length * 8)));
            $refreshToken = RefreshToken::createEmpty();
            $refreshToken = $refreshToken->create(
                $refreshTokenId,
                $grantTypeData->getResourceOwnerId(),
                $grantTypeData->getClient()->getPublicId(),
                $grantTypeData->getParameters(),
                $grantTypeData->getMetadatas(), $expiresAt,
                null);
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
