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

namespace OAuth2Framework\Component\Core\Client;

use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;

/**
 * Class ClientId.
 */
class ClientId extends ResourceOwnerId
{
    /**
     * @param string $value
     *
     * @return ClientId
     */
    public static function create(string $value): self
    {
        return new self($value);
    }
}
