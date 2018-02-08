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

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class InitialAccessTokenRepository implements \OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository
{
    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * InitialAccessTokenRepository constructor.
     *
     * @param AdapterInterface $cache
     */
    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function find(InitialAccessTokenId $initialAccessTokenId): ? InitialAccessToken
    {
        $initialAccessToken = $this->getFromCache($initialAccessTokenId);

        return $initialAccessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function save(InitialAccessToken $initialAccessToken)
    {
        $initialAccessToken->eraseMessages();
        $this->cacheObject($initialAccessToken);
    }

    /**
     * @param InitialAccessTokenId $initialAccessTokenId
     *
     * @return InitialAccessToken|null
     */
    private function getFromCache(InitialAccessTokenId $initialAccessTokenId): ? InitialAccessToken
    {
        $itemKey = sprintf('oauth2-initial_access_token-%s', $initialAccessTokenId->getValue());
        $item = $this->cache->getItem($itemKey);
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    /**
     * @param InitialAccessToken $initialAccessToken
     */
    private function cacheObject(InitialAccessToken $initialAccessToken)
    {
        $itemKey = sprintf('oauth2-initial_access_token-%s', $initialAccessToken->getUserAccountId()->getValue());
        $item = $this->cache->getItem($itemKey);
        $item->set($initialAccessToken);
        $item->tag(['oauth2_server', 'initial_access_token', $itemKey]);
        $this->cache->save($item);
    }
}
