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

namespace OAuth2Framework\Component\Server\Model\Event;

use OAuth2Framework\Component\Server\Model\Id\Id;

interface EventStoreInterface
{
    /**
     * @param Event $event
     */
    public function save(Event $event);

    /**
     * @param Id $id
     *
     * @return Event[]
     */
    public function getEvents(Id $id): array;
}
