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

namespace OAuth2Framework\Bundle\Server\Tests\TestBundle\Listener;

use OAuth2Framework\Component\Server\Event\RefreshToken\RefreshTokenCreatedEvent;

final class RefreshTokenCreatedListener
{
    /**
     * @var RefreshTokenCreatedEvent[]
     */
    private $events = [];

    /**
     * @param RefreshTokenCreatedEvent $event
     */
    public function handle(RefreshTokenCreatedEvent $event)
    {
        $this->events[] = $event;
    }

    /**
     * @return RefreshTokenCreatedEvent[]
     */
    public function getEvents()
    {
        return $this->events;
    }
}
