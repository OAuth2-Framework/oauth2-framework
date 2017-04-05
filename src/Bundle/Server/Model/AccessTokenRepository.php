<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Model;

use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenId;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use Ramsey\Uuid\Uuid;
use SimpleBus\Message\Recorder\RecordsMessages;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var RecordsMessages
     */
    private $eventRecorder;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * AccessTokenRepository constructor.
     *
     * @param EventStoreInterface $eventStore
     * @param RecordsMessages     $eventRecorder
     * @param int                 $lifetime
     */
    public function __construct(EventStoreInterface $eventStore, RecordsMessages $eventRecorder, int $lifetime)
    {
        $this->eventStore = $eventStore;
        $this->eventRecorder = $eventRecorder;
        $this->lifetime = $lifetime;
    }

    /**
     * @param AdapterInterface $cache
     */
    public function enableDomainObjectCaching(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function find(AccessTokenId $accessTokenId)
    {
        $accessToken = $this->getFromCache($accessTokenId);
        if (null === $accessToken) {
            $events = $this->eventStore->getEvents($accessTokenId);
            if (!empty($events)) {
                $accessToken = AccessToken::createEmpty();
                foreach ($events as $event) {
                    $accessToken = $accessToken->apply($event);
                }

                $this->cacheObject($accessToken);
            }
        }

        return $accessToken;
    }

    /**
     * @param AccessToken $accessToken
     */
    public function save(AccessToken $accessToken)
    {
        $events = $accessToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
            $this->eventRecorder->record($event);
        }
        $accessToken->eraseMessages();
        $this->cacheObject($accessToken);
    }

    /**
     * {@inheritdoc}
     */
    public function create(ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes, ? RefreshTokenId $refreshTokenId, ? ResourceServerId $resourceServerId, ? \DateTimeImmutable $expiresAt) : AccessToken
    {
        if (null === $expiresAt) {
            $expiresAt = new \DateTimeImmutable(sprintf('now +%u seconds', $this->lifetime));
        }

        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            AccessTokenId::create(Uuid::uuid4()->toString()),
            $resourceOwnerId, $clientId, $parameters, $metadatas, $scopes, $expiresAt, $refreshTokenId, $resourceServerId);

        return $accessToken;
    }

    /**
     * @param AccessTokenId $accessTokenId
     *
     * @return AccessToken|null
     */
    private function getFromCache(AccessTokenId $accessTokenId): ? AccessToken
    {
        $itemKey = sprintf('oauth2-access_token-%s', $accessTokenId->getValue());
        if (null !== $this->cache) {
            $item = $this->cache->getItem($itemKey);
            if ($item->isHit()) {
                return $item->get();
            }
        }

        return null;
    }

    /**
     * @param AccessToken $accessToken
     */
    private function cacheObject(AccessToken $accessToken)
    {
        $itemKey = sprintf('oauth2-access_token-%s', $accessToken->getTokenId()->getValue());
        if (null !== $this->cache) {
            $item = $this->cache->getItem($itemKey);
            $item->set($accessToken);
            $item->tag(['oauth2_server', 'access_token', $itemKey]);
            $this->cache->save($item);
        }
    }
}
