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
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\Event\Event;
use OAuth2Framework\Component\Server\Core\Event\EventId;
use OAuth2Framework\Component\Server\Core\Id\Id;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Core\DomainObject;

final class ClientCreatedEvent extends Event
{
    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * @var UserAccountId|null
     */
    private $userAccountId;

    /**
     * ClientCreatedEvent constructor.
     *
     * @param ClientId                $clientId
     * @param DataBag                 $parameters
     * @param UserAccountId|null      $userAccountId
     * @param \DateTimeImmutable|null $recordedOn
     * @param EventId|null            $eventId
     */
    protected function __construct(ClientId $clientId, DataBag $parameters, ? UserAccountId $userAccountId, ? \DateTimeImmutable $recordedOn, ? EventId $eventId)
    {
        parent::__construct($recordedOn, $eventId);
        $this->clientId = $clientId;
        $this->parameters = $parameters;
        $this->userAccountId = $userAccountId;
    }

    /**
     * @param ClientId           $clientId
     * @param DataBag            $parameters
     * @param UserAccountId|null $userAccountId
     *
     * @return ClientCreatedEvent
     */
    public static function create(ClientId $clientId, DataBag $parameters, ? UserAccountId $userAccountId): ClientCreatedEvent
    {
        return new self($clientId, $parameters, $userAccountId, null, null);
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $clientId = ClientId::create($json->domain_id);
        $eventId = EventId::create($json->event_id);
        $recordedOn = \DateTimeImmutable::createFromFormat('U', (string) $json->recorded_on);
        $userAccountId = null === $json->payload->user_account_id ? null : UserAccountId::create($json->payload->user_account_id);
        $parameters = DataBag::create((array) $json->payload->parameters);

        return new self(
            $clientId,
            $parameters,
            $userAccountId,
            $recordedOn,
            $eventId
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/client/created/1.0/schema';
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->clientId;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return (object) [
            'user_account_id' => $this->userAccountId ? $this->userAccountId->getValue() : null,
            'parameters' => (object) $this->parameters->all(),
        ];
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return DataBag
     */
    public function getParameters(): DataBag
    {
        return $this->parameters;
    }

    /**
     * @return null|UserAccountId
     */
    public function getOwnerId(): ? UserAccountId
    {
        return $this->userAccountId;
    }
}
