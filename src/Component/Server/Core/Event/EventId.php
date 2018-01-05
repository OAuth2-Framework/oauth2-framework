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

final class EventId extends Id
{
    /**
     * @param string $value
     *
     * @return EventId
     */
    public static function create(string $value): self
    {
        return new self($value);
    }
}
