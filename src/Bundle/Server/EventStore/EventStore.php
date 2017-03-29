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

namespace OAuth2Framework\Bundle\Server\EventStore;

use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\Id\Id;
use OAuth2Framework\Component\Server\Schema\DomainConverter;
use Psr\Cache\CacheItemPoolInterface;

final class EventStore implements EventStoreInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var DomainConverter
     */
    private $domainConverter;

    /**
     * ClientRepository constructor.
     *
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
        $this->domainConverter = new DomainConverter();
    }

    /**
     * {@inheritdoc}
     */
    public function save(Event $event)
    {
        $json = $this->domainConverter->toJson($event);
        $item = $this->cache->getItem($event->getEventId()->getValue());
        $item->set($json);
        $this->cache->save($item);
    }

    /**
     * @param Id $id
     *
     * @return Event[]
     */
    public function getEvents(Id $id): array
    {
        return [];
        /*$item = $this->cache->getItem($id->getValue());
        if ($item->isHit()) {
            return $item->get();
        }

        $client = null;
        $events = $this->eventStore->getEvents($id);
        if (!empty($events)) {
            $client = Client::createEmpty();
            foreach ($events as $event) {
                $client = $client->apply($event);
            }
            $item->set($client);
            $this->cache->save($item);
        }

        return $client;*/
    }
}
