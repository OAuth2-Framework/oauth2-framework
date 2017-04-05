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

namespace OAuth2Framework\Component\Server\Event\Client;

use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\Event\EventId;
use OAuth2Framework\Component\Server\Model\Id\Id;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;

final class ClientParametersUpdatedEvent extends Event
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
     * ClientParametersUpdatedEvent constructor.
     *
     * @param ClientId                $clientId
     * @param DataBag                 $parameters
     * @param \DateTimeImmutable|null $recordedOn
     * @param null|EventId            $eventId
     */
    protected function __construct(ClientId $clientId, DataBag $parameters, ? \DateTimeImmutable $recordedOn, ? EventId $eventId)
    {
        parent::__construct($recordedOn, $eventId);
        $this->clientId = $clientId;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/client/parameters-updated/1.0/schema';
    }

    /**
     * @param ClientId $clientId
     * @param DataBag  $parameters
     *
     * @return ClientParametersUpdatedEvent
     */
    public static function create(ClientId $clientId, DataBag $parameters): ClientParametersUpdatedEvent
    {
        return new self($clientId, $parameters, null, null);
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
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getClientId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return (object) [
            'parameters' => (object) $this->parameters->all(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObjectInterface
    {
        $clientId = ClientId::create($json->domain_id);
        $eventId = EventId::create($json->event_id);
        $recordedOn = \DateTimeImmutable::createFromFormat('U', (string) $json->recorded_on);
        $parameters = DataBag::createFromArray((array) $json->payload->parameters);

        return new self(
            $clientId,
            $parameters,
            $recordedOn,
            $eventId
        );
    }
}
