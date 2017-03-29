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

namespace OAuth2Framework\Bundle\Server\Tests\TestBundle\Listener;

use OAuth2Framework\Component\Server\Event\Client\ClientCreatedEvent;

final class ClientCreatedListener
{
    /**
     * @var ClientCreatedEvent[]
     */
    private $events = [];

    /**
     * @param ClientCreatedEvent $event
     */
    public function handle(ClientCreatedEvent $event)
    {
        $this->events[] = $event;
    }

    /**
     * @return ClientCreatedEvent[]
     */
    public function getEvents()
    {
        return $this->events;
    }
}
