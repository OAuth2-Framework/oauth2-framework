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

use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Event\EventStore;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository as RefreshTokenRepositoryInterface;
use SimpleBus\Message\Bus\MessageBus;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var MessageBus
     */
    private $eventBus;

    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * RefreshTokenRepository constructor.
     *
     * @param EventStore       $eventStore
     * @param MessageBus       $eventBus
     * @param AdapterInterface $cache
     */
    public function __construct(EventStore $eventStore, MessageBus $eventBus, AdapterInterface $cache)
    {
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function find(RefreshTokenId $refreshTokenId)
    {
        $refreshToken = $this->getFromCache($refreshTokenId);
        if (null === $refreshToken) {
            $events = $this->eventStore->findAllForDomainId($refreshTokenId);
            if (!empty($events)) {
                $refreshToken = $this->getFromEvents($events);
                $this->cacheObject($refreshToken);
            }
        }

        return $refreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function save(RefreshToken $refreshToken)
    {
        $events = $refreshToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
            $this->eventBus->handle($event);
        }
        $refreshToken->eraseMessages();
        $this->cacheObject($refreshToken);
    }

    /**
     * @param Event[] $events
     *
     * @return RefreshToken
     */
    private function getFromEvents(array $events): RefreshToken
    {
        $refreshToken = RefreshToken::createEmpty();
        foreach ($events as $event) {
            $refreshToken = $refreshToken->apply($event);
        }

        return $refreshToken;
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
}
