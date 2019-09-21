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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Repository;

use Assert\Assertion;
use OAuth2Framework\Component\Core\AccessToken\AccessToken as AccessTokenInterface;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository as AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AccessToken;
use Psr\Cache\CacheItemPoolInterface;

final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function find(AccessTokenId $accessTokenId): ?AccessTokenInterface
    {
        $item = $this->cache->getItem('AccessToken-'.$accessTokenId->getValue());
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    public function save(AccessTokenInterface $accessToken): void
    {
        Assertion::isInstanceOf($accessToken, AccessToken::class, 'Unsupported access token class');
        $item = $this->cache->getItem('AccessToken-'.$accessToken->getId()->getValue());
        $item->set($accessToken);
        $this->cache->save($item);
    }

    public function create(ClientId $clientId, ResourceOwnerId $resourceOwnerId, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId): AccessTokenInterface
    {
        return new AccessToken(new AccessTokenId(bin2hex(random_bytes(32))), $clientId, $resourceOwnerId, $expiresAt, $parameter, $metadata, $resourceServerId);
    }
}
