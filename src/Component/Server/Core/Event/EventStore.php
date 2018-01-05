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

namespace OAuth2Framework\Component\Server\Core\Event;

use OAuth2Framework\Component\Server\Core\Id\Id;

interface EventStore
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
    public function findAllForDomainId(Id $id): array;
}
