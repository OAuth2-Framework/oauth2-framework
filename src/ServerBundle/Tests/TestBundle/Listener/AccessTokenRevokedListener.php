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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Listener;

use OAuth2Framework\Component\Core\AccessToken\Event\AccessTokenRevokedEvent;

class AccessTokenRevokedListener
{
    /**
     * @var AccessTokenRevokedEvent[]
     */
    private $events = [];

    /**
     * @param AccessTokenRevokedEvent $event
     */
    public function handle(AccessTokenRevokedEvent $event)
    {
        $this->events[] = $event;
    }

    /**
     * @return AccessTokenRevokedEvent[]
     */
    public function getEvents()
    {
        return $this->events;
    }
}
