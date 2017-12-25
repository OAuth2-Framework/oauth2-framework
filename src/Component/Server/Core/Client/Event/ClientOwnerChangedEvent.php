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

namespace OAuth2Framework\Component\Server\Core\Client\Event;

use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\Event\Event;
use OAuth2Framework\Component\Server\Core\Event\EventId;
use OAuth2Framework\Component\Server\Core\Id\Id;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Core\DomainObject;

final class ClientOwnerChangedEvent extends Event
{
    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var UserAccountId
     */
    private $newOwnerId;

    /**
     * ClientOwnerChangedEvent constructor.
     *
     * @param ClientId                $clientId
     * @param UserAccountId           $newOwnerId
     * @param \DateTimeImmutable|null $recordedOn
     * @param EventId|null            $eventId
     */
    protected function __construct(ClientId $clientId, UserAccountId $newOwnerId, ? \DateTimeImmutable $recordedOn, ? EventId $eventId)
    {
        parent::__construct($recordedOn, $eventId);
        $this->clientId = $clientId;
        $this->newOwnerId = $newOwnerId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/client/owner-changed/1.0/schema';
    }

    /**
     * @param ClientId      $clientId
     * @param UserAccountId $newOwnerId
     *
     * @return ClientOwnerChangedEvent
     */
    public static function create(ClientId $clientId, UserAccountId $newOwnerId): self
    {
        return new self($clientId, $newOwnerId, null, null);
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $clientId = ClientId::create($json->domain_id);
        $eventId = EventId::create($json->event_id);
        $recordedOn = \DateTimeImmutable::createFromFormat('U', (string) $json->recorded_on);
        $userAccountId = null === $json->payload->new_owner_id ? null : UserAccountId::create($json->payload->new_owner_id);

        return new self(
            $clientId,
            $userAccountId,
            $recordedOn,
            $eventId
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getClientId();
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return UserAccountId
     */
    public function getNewOwnerId(): UserAccountId
    {
        return $this->newOwnerId;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return (object) [
            'new_owner_id' => $this->newOwnerId->getValue(),
        ];
    }
}
