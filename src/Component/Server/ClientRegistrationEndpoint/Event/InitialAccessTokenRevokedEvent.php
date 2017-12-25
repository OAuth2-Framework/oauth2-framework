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

namespace OAuth2Framework\Component\Server\ClientRegistrationEndpoint\Event;

use OAuth2Framework\Component\Server\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\Server\Core\DomainObject;
use OAuth2Framework\Component\Server\Core\Event\Event;
use OAuth2Framework\Component\Server\Core\Event\EventId;
use OAuth2Framework\Component\Server\Core\Id\Id;

final class InitialAccessTokenRevokedEvent extends Event
{
    /**
     * @var InitialAccessTokenId
     */
    private $initialAccessTokenId;

    /**
     * InitialAccessTokenRevokedEvent constructor.
     *
     * @param InitialAccessTokenId    $initialAccessTokenId
     * @param \DateTimeImmutable|null $recordedOn
     * @param EventId|null            $eventId
     */
    protected function __construct(InitialAccessTokenId $initialAccessTokenId, ? \DateTimeImmutable $recordedOn, ? EventId $eventId)
    {
        parent::__construct($recordedOn, $eventId);
        $this->initialAccessTokenId = $initialAccessTokenId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/revoked/1.0/schema';
    }

    /**
     * @param InitialAccessTokenId $initialAccessTokenId
     *
     * @return InitialAccessTokenRevokedEvent
     */
    public static function create(InitialAccessTokenId $initialAccessTokenId): self
    {
        return new self($initialAccessTokenId, null, null);
    }

    /**
     * @return InitialAccessTokenId
     */
    public function getInitialAccessTokenId(): InitialAccessTokenId
    {
        return $this->initialAccessTokenId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getInitialAccessTokenId();
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
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $initialAccessTokenId = InitialAccessTokenId::create($json->domain_id);
        $eventId = EventId::create($json->event_id);
        $recordedOn = \DateTimeImmutable::createFromFormat('U', (string) $json->recorded_on);

        return new self(
            $initialAccessTokenId,
            $recordedOn,
            $eventId
        );
    }
}