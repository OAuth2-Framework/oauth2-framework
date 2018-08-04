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

use OAuth2Framework\Component\RefreshTokenGrant\Event\RefreshTokenRevokedEvent;

class RefreshTokenRevokedListener
{
    /**
     * @var RefreshTokenRevokedEvent[]
     */
    private $events = [];

    public function handle(RefreshTokenRevokedEvent $event)
    {
        $this->events[] = $event;
    }

    /**
     * @return RefreshTokenRevokedEvent[]
     */
    public function getEvents()
    {
        return $this->events;
    }
}
