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

namespace OAuth2Framework\Bundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository as RefreshTokenRepositoryInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * RefreshTokenRepository constructor.
     *
     * @param AdapterInterface $cache
     */
    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
        $this->initRefreshTokens();
    }

    /**
     * {@inheritdoc}
     */
    public function find(RefreshTokenId $refreshTokenId)
    {
        $refreshToken = $this->getFromCache($refreshTokenId);

        return $refreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function save(RefreshToken $refreshToken)
    {
        $refreshToken->eraseMessages();
        $this->cacheObject($refreshToken);
    }

    /**
     * @param RefreshTokenId $refreshTokenId
     *
     * @return RefreshToken|null
     */
    private function getFromCache(RefreshTokenId $refreshTokenId): ? RefreshToken
    {
        $itemKey = sprintf('oauth2-refresh_token-%s', $refreshTokenId->getValue());
        $item = $this->cache->getItem($itemKey);
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    /**
     * @param RefreshToken $refreshToken
     */
    private function cacheObject(RefreshToken $refreshToken)
    {
        $itemKey = sprintf('oauth2-refresh_token-%s', $refreshToken->getTokenId()->getValue());
        $item = $this->cache->getItem($itemKey);
        $item->set($refreshToken);
        $item->tag(['oauth2_server', 'refresh_token', $itemKey]);
        $this->cache->save($item);
    }

    private function initRefreshTokens()
    {
        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create('VALID_REFRESH_TOKEN'),
            UserAccountId::create('john.1'),
            ClientId::create('CLIENT_ID_3'),
            DataBag::create([]),
            DataBag::create([]),
            new \DateTimeImmutable('now +1 day'),
            null
        );
        $refreshToken->eraseMessages();
        $this->save($refreshToken);

        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create('REVOKED_REFRESH_TOKEN'),
            UserAccountId::create('john.1'),
            ClientId::create('CLIENT_ID_3'),
            DataBag::create([]),
            DataBag::create([]),
            new \DateTimeImmutable('now +1 day'),
            null
        );
        $refreshToken = $refreshToken->markAsRevoked();
        $refreshToken->eraseMessages();
        $this->save($refreshToken);

        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create('EXPIRED_REFRESH_TOKEN'),
            UserAccountId::create('john.1'),
            ClientId::create('CLIENT_ID_3'),
            DataBag::create([]),
            DataBag::create([]),
            new \DateTimeImmutable('now -1 day'),
            null
        );
        $refreshToken->eraseMessages();
        $this->save($refreshToken);
    }
}
