<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Model;

use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessToken;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenId;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use Ramsey\Uuid\Uuid;
use SimpleBus\Message\Recorder\RecordsMessages;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class InitialAccessTokenRepository implements InitialAccessTokenRepositoryInterface
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
     * InitialAccessTokenRepository constructor.
     *
     * @param EventStoreInterface $eventStore
     * @param RecordsMessages     $eventRecorder
     */
    public function __construct(EventStoreInterface $eventStore, RecordsMessages $eventRecorder)
    {
        $this->eventStore = $eventStore;
        $this->eventRecorder = $eventRecorder;
    }

    /**
     * {@inheritdoc}
     */
    public function create(?UserAccountId $userAccountId, ?\DateTimeImmutable $expiresAt): InitialAccessToken
    {
        $initialAccessTokeId = InitialAccessTokenId::create(Uuid::uuid4()->toString());
        $initialAccessToken = InitialAccessToken::createEmpty();
        $initialAccessToken = $initialAccessToken->create($initialAccessTokeId, $userAccountId, $expiresAt);

        return $initialAccessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function find(InitialAccessTokenId $initialAccessTokenId): ?InitialAccessToken
    {
        $initialAccessToken = $this->getFromCache($initialAccessTokenId);
        if (null === $initialAccessToken) {
            $events = $this->eventStore->getEvents($initialAccessTokenId);
            if (!empty($events)) {
                $initialAccessToken = InitialAccessToken::createEmpty();
                foreach ($events as $event) {
                    $initialAccessToken = $initialAccessToken->apply($event);
                }

                $this->cacheObject($initialAccessToken);
            }
        }

        return $initialAccessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function save(InitialAccessToken $initialAccessToken)
    {
        $events = $initialAccessToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
            $this->eventRecorder->record($event);
        }
        $initialAccessToken->eraseMessages();
        $this->cacheObject($initialAccessToken);
    }

    /**
     * @param AdapterInterface $cache
     */
    public function enableDomainObjectCaching(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param InitialAccessTokenId $initialAccessTokenId
     *
     * @return InitialAccessToken|null
     */
    private function getFromCache(InitialAccessTokenId $initialAccessTokenId): ?InitialAccessToken
    {
        $itemKey = sprintf('oauth2-initial_access_token-%s', $initialAccessTokenId->getValue());
        if (null !== $this->cache) {
            $item = $this->cache->getItem($itemKey);
            if ($item->isHit()) {
                return $item->get();
            }
        }
    }

    /**
     * @param InitialAccessToken $initialAccessToken
     */
    private function cacheObject(InitialAccessToken $initialAccessToken)
    {
        $itemKey = sprintf('oauth2-initial_access_token-%s', $initialAccessToken->getUserAccountId()->getValue());
        if (null !== $this->cache) {
            $item = $this->cache->getItem($itemKey);
            $item->set($initialAccessToken);
            $item->tag(['oauth2_server', 'initial_access_token', $itemKey]);
            $this->cache->save($item);
        }
    }
}
