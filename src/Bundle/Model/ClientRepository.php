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

namespace OAuth2Framework\Bundle\Model;

use OAuth2Framework\Component\Model\Client\Client;
use OAuth2Framework\Component\Model\Client\ClientId;
use OAuth2Framework\Component\Model\Client\ClientRepositoryInterface;
use OAuth2Framework\Component\Model\Event\Event;
use OAuth2Framework\Component\Model\Event\EventStoreInterface;
use SimpleBus\Message\Recorder\RecordsMessages;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class ClientRepository implements ClientRepositoryInterface
{
    /**
     * @var EventStoreInterface
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
     * ClientRepository constructor.
     *
     * @param EventStoreInterface $eventStore
     * @param RecordsMessages     $eventRecorder
     * @param AdapterInterface    $cache
     */
    public function __construct(EventStoreInterface $eventStore, RecordsMessages $eventRecorder, AdapterInterface $cache)
    {
        $this->eventStore = $eventStore;
        $this->eventRecorder = $eventRecorder;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function find(ClientId $clientId): ? Client
    {
        $client = $this->getFromCache($clientId);
        if (null === $client) {
            $events = $this->eventStore->getEvents($clientId);
            if (!empty($events)) {
                $client = $this->getFromEvents($events);
                $this->cacheObject($client);
            }
        }

        return $client;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Client $client)
    {
        $events = $client->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
            $this->eventRecorder->record($event);
        }
        $client->eraseMessages();
        $this->cacheObject($client);
    }

    /**
     * @param Event[] $events
     *
     * @return Client
     */
    private function getFromEvents(array $events): Client
    {
        $client = Client::createEmpty();
        foreach ($events as $event) {
            $client = $client->apply($event);
        }

        return $client;
    }

    /**
     * @param ClientId $clientId
     *
     * @return Client|null
     */
    private function getFromCache(ClientId $clientId): ? Client
    {
        $itemKey = sprintf('oauth2-client-%s', $clientId->getValue());
        $item = $this->cache->getItem($itemKey);
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    /**
     * @param Client $client
     */
    private function cacheObject(Client $client)
    {
        $itemKey = sprintf('oauth2-client-%s', $client->getPublicId()->getValue());
        $item = $this->cache->getItem($itemKey);
        $item->set($client);
        $item->tag(['oauth2_server', 'client', $itemKey]);
        $this->cache->save($item);
    }
}
