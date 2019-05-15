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
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken as RefreshTokenInterface;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository as RefreshTokenRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;

final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
        $this->load();
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

    private function load(): void
    {
        foreach ($this->getData() as $datum) {
            $refreshToken = new RefreshToken(
                new RefreshTokenId($datum['refresh_token_id']),
                new ClientId($datum['client_id']),
                $datum['resource_owner_id'],
                $datum['expires_at'],
                new DataBag($datum['parameter']),
                new DataBag($datum['metadata']),
                $datum['resource_server_id']
            );
            if ($datum['is_revoked']) {
                $refreshToken->markAsRevoked();
            }
            $this->save($refreshToken);
        }
    }

    private function getData(): array
    {
        return [
            [
                'refresh_token_id' => 'VALID_REFRESH_TOKEN',
                'client_id' => 'CLIENT_ID_3',
                'resource_owner_id' => new UserAccountId('john.1'),
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
            ],
            [
                'refresh_token_id' => 'REVOKED_REFRESH_TOKEN',
                'client_id' => 'CLIENT_ID_3',
                'resource_owner_id' => new UserAccountId('john.1'),
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => true,
            ],
            [
                'refresh_token_id' => 'EXPIRED_REFRESH_TOKEN',
                'client_id' => 'CLIENT_ID_3',
                'resource_owner_id' => new UserAccountId('john.1'),
                'expires_at' => new \DateTimeImmutable('now -1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
            ],
        ];
    }
}
