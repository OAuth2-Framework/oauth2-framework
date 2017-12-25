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

namespace OAuth2Framework\Component\Server\IssuerDiscoveryEndpoint;

use OAuth2Framework\Component\Server\Core\Id\Id;

final class ResourceId extends Id
{
    /**
     * @param string $value
     *
     * @return ResourceId
     */
    public static function create(string $value): self
    {
        return new self($value);
    }
}