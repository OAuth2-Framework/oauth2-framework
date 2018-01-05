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

namespace OAuth2Framework\Component\Server\Core\Event;

use OAuth2Framework\Component\Server\Core\Id\Id;
use OAuth2Framework\Component\Server\Core\DomainObject;
use Ramsey\Uuid\Uuid;

abstract class Event implements DomainObject
{
    /**
     * @var EventId
     */
    private $eventId;

    /**
     * @var \DateTimeImmutable
     */
    private $recordedOn;

    /**
     * Event constructor.
     *
     * @param \DateTimeImmutable|null $recordedOn
     * @param EventId|null            $eventId
     */
    protected function __construct(? \DateTimeImmutable $recordedOn, ? EventId $eventId)
    {
        if (null === $recordedOn || null === $eventId) {
            $this->recordedOn = new \DateTimeImmutable();
            $this->eventId = EventId::create(Uuid::uuid4()->toString());
        } else {
            $this->recordedOn = $recordedOn;
            $this->eventId = $eventId;
        }
    }

    /**
     * @return EventId
     */
    public function getEventId(): EventId
    {
        return $this->eventId;
    }

    /**
     * @return mixed
     */
    abstract public function getPayload();

    /**
     * @return Id
     */
    abstract public function getDomainId(): Id;

    /**
     * @return \DateTimeImmutable
     */
    public function getRecordedOn(): \DateTimeImmutable
    {
        return $this->recordedOn;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return get_class($this);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [
            '$schema' => $this->getSchema(),
            'event_id' => $this->getEventId()->getValue(),
            'type' => get_class($this),
            'domain_id' => $this->getDomainId()->getValue(),
            'recorded_on' => $this->getRecordedOn()->getTimestamp(),
        ];
        $payload = $this->getPayload();
        if (null !== $payload) {
            $data['payload'] = $payload;
        }

        return $data;
    }
}
