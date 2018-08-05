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
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;

class ClientParametersUpdatedEvent extends Event
{
    private $clientId;

    private $parameters;

    protected function __construct(ClientId $clientId, DataBag $parameters)
    {
        $this->clientId = $clientId;
        $this->parameters = $parameters;
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/client/parameters-updated/1.0/schema';
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getParameters(): DataBag
    {
        return $this->parameters;
    }

    public function getDomainId(): Id
    {
        return $this->getClientId();
    }

    public function getPayload()
    {
        return [
            'parameters' => (object) $this->parameters->all(),
        ];
    }
}
