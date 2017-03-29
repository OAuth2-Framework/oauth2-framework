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

namespace OAuth2Framework\Component\Server\Tests\Stub\Event;

use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\Id\Id;
use OAuth2Framework\Component\Server\Schema\DomainConverter;

final class EventStore implements EventStoreInterface
{
    /**
     * @var DomainConverter
     */
    private $domainConverter;

    /**
     * @var string[]
     */
    private $events = [];

    /**
     * EventStore constructor.
     *
     * @param DomainConverter $domainConverter
     */
    public function __construct(DomainConverter $domainConverter)
    {
        $this->domainConverter = $domainConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Event $event)
    {
        $domainId = $event->getDomainId()->getValue();
        if (!array_key_exists($domainId, $this->events)) {
            $this->events[$domainId] = [];
        }

        $json = $this->domainConverter->toJson($event);
        $this->events[$domainId][] = $json;
    }

    /**
     * {@inheritdoc}
     */
    public function getEvents(Id $id): array
    {
        if (!array_key_exists($id->getValue(), $this->events)) {
            return [];
        }

        $jsons = $this->events[$id->getValue()];
        $events = [];
        foreach ($jsons as $json) {
            $events[] = $this->domainConverter->fromJson($json);
        }

        return $events;
    }
}
