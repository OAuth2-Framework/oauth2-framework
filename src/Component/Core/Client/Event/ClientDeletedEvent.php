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
use OAuth2Framework\Component\Core\Domain\DomainObject;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;

class ClientDeletedEvent extends Event
{
    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * ClientDeletedEvent constructor.
     */
    protected function __construct(ClientId $clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/client/deleted/1.0/schema';
    }

    /**
     * @return ClientDeletedEvent
     */
    public static function create(ClientId $clientId): self
    {
        return new self($clientId);
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $clientId = new ClientId($json->domain_id);

        return new self($clientId);
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
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }
}
