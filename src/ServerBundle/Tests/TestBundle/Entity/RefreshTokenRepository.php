<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use Assert\Assertion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken as RefreshTokenInterface;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository as RefreshTokenRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;

final class RefreshTokenRepository implements RefreshTokenRepositoryInterface, ServiceEntityRepositoryInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function find(RefreshTokenId $refreshTokenId): ?RefreshTokenInterface
    {
        $item = $this->cache->getItem('RefreshToken-'.$refreshTokenId->getValue());
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    public function save(RefreshTokenInterface $refreshToken): void
    {
        Assertion::isInstanceOf($refreshToken, RefreshToken::class, 'Unsupported refresh token class');
        $item = $this->cache->getItem('RefreshToken-'.$refreshToken->getId()->getValue());
        $item->set($refreshToken);
        $this->cache->save($item);
    }

    public function create(ClientId $clientId, ResourceOwnerId $resourceOwnerId, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId): RefreshTokenInterface
    {
        return new RefreshToken(
            new RefreshTokenId(\bin2hex(\random_bytes(32))),
            $clientId,
            $resourceOwnerId,
            $expiresAt,
            $parameter,
            $metadata,
            $resourceServerId
        );
    }
}
