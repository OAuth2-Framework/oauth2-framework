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

namespace OAuth2Framework\Component\Core\Client\Event;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Domain\DomainObject;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;

class ClientParametersUpdatedEvent extends Event
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
     */
    protected function __construct(ClientId $clientId, DataBag $parameters)
    {
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
     * @return ClientParametersUpdatedEvent
     */
    public static function create(ClientId $clientId, DataBag $parameters): self
    {
        return new self($clientId, $parameters);
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

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
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $clientId = new ClientId($json->domain_id);
        $parameters = new DataBag((array) $json->payload->parameters);

        return new self(
            $clientId,
            $parameters
        );
    }
}
