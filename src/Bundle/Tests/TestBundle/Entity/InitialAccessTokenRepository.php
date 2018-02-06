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
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Event\EventStore;
use SimpleBus\Message\Recorder\RecordsMessages;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class InitialAccessTokenRepository implements \OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var RecordsMessages
     */
    private $eventRecorder;

    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * InitialAccessTokenRepository constructor.
     *
     * @param EventStore       $eventStore
     * @param RecordsMessages  $eventRecorder
     * @param AdapterInterface $cache
     */
    public function __construct(EventStore $eventStore, RecordsMessages $eventRecorder, AdapterInterface $cache)
    {
        $this->eventStore = $eventStore;
        $this->eventRecorder = $eventRecorder;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function find(InitialAccessTokenId $initialAccessTokenId): ? InitialAccessToken
    {
        $initialAccessToken = $this->getFromCache($initialAccessTokenId);
        if (null === $initialAccessToken) {
            $events = $this->eventStore->findAllForDomainId($initialAccessTokenId);
            if (!empty($events)) {
                $initialAccessToken = $this->getFromEvents($events);
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
     * @param Event[] $events
     *
     * @return InitialAccessToken
     */
    private function getFromEvents(array $events): InitialAccessToken
    {
        $initialAccessToken = InitialAccessToken::createEmpty();
        foreach ($events as $event) {
            $initialAccessToken = $initialAccessToken->apply($event);
        }

        return $initialAccessToken;
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
