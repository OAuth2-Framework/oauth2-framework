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

namespace OAuth2Framework\Component\Server\Event\AuthCode;

use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeId;
use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\Event\EventId;
use OAuth2Framework\Component\Server\Model\Id\Id;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;

final class AuthCodeMarkedAsUsedEvent extends Event
{
    /**
     * @var AuthCodeId
     */
    private $authCodeId;

    /**
     * AuthCodeMarkedAsUsedEvent constructor.
     *
     * @param AuthCodeId              $authCodeId
     * @param \DateTimeImmutable|null $recordedOn
     * @param EventId|null            $eventId
     */
    protected function __construct(AuthCodeId $authCodeId, ? \DateTimeImmutable $recordedOn, ? EventId $eventId)
    {
        parent::__construct($recordedOn, $eventId);
        $this->authCodeId = $authCodeId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/auth-code/marked-as-used/1.0/schema';
    }

    /**
     * @param AuthCodeId $authCodeId
     *
     * @return AuthCodeMarkedAsUsedEvent
     */
    public static function create(AuthCodeId $authCodeId): AuthCodeMarkedAsUsedEvent
    {
        return new self($authCodeId, null, null);
    }

    /**
     * @return AuthCodeId
     */
    public function getAuthCodeId(): AuthCodeId
    {
        return $this->authCodeId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getAuthCodeId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObjectInterface
    {
        $authCodeId = AuthCodeId::create($json->domain_id);
        $eventId = EventId::create($json->event_id);
        $recordedOn = \DateTimeImmutable::createFromFormat('U', (string) $json->recorded_on);

        return new self($authCodeId, $recordedOn, $eventId);
    }
}
