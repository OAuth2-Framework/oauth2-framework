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

namespace OAuth2Framework\Bundle\Server\Model;

use OAuth2Framework\Bundle\Server\Service\RandomIdGenerator;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshToken;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenId;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use SimpleBus\Message\Bus\MessageBus;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
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
     * @var EventStoreInterface
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
     * @param int                 $minLength
     * @param int                 $maxLength
     * @param int                 $lifetime
     * @param EventStoreInterface $eventStore
     * @param MessageBus          $eventBus
     * @param AdapterInterface    $cache
     */
    public function __construct(int $minLength, int $maxLength, int $lifetime, EventStoreInterface $eventStore, MessageBus $eventBus, AdapterInterface $cache)
    {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->lifetime = $lifetime;
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function create(ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes, ? ResourceServerId $resourceServerId, ? \DateTimeImmutable $expiresAt): RefreshToken
    {
        if (null === $expiresAt) {
            $expiresAt = new \DateTimeImmutable(sprintf('now +%u seconds', $this->lifetime));
        }

        $refreshTokenId = RefreshTokenId::create(RandomIdGenerator::generate($this->minLength, $this->maxLength));
        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create($refreshTokenId, $resourceOwnerId, $clientId, $parameters, $metadatas, $scopes, $expiresAt, $resourceServerId);

        return $refreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function find(RefreshTokenId $refreshTokenId)
    {
        $refreshToken = $this->getFromCache($refreshTokenId);
        if (null === $refreshToken) {
            $events = $this->eventStore->getEvents($refreshTokenId);
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
