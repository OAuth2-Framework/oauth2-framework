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

namespace OAuth2Framework\Component\Server\Model\ResourceServer;

use OAuth2Framework\Component\Server\Model\Id\Id;

final class ResourceServerId extends Id
{
    /**
     * @param string $value
     *
     * @return ResourceServerId
     */
    public static function create(string $value): ResourceServerId
    {
        return new self($value);
    }
}
