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

use OAuth2Framework\Component\Server\Event\AccessToken\AccessTokenCreatedEvent;

final class AccessTokenCreatedListener
{
    /**
     * @var AccessTokenCreatedEvent[]
     */
    private $events = [];

    /**
     * @param AccessTokenCreatedEvent $event
     */
    public function handle(AccessTokenCreatedEvent $event)
    {
        $this->events[] = $event;
    }

    /**
     * @return AccessTokenCreatedEvent[]
     */
    public function getEvents()
    {
        return $this->events;
    }
}
