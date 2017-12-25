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

namespace OAuth2Framework\Component\Server\RefreshTokenGrant;

use OAuth2Framework\Component\Server\Core\Token\TokenId;

final class RefreshTokenId extends TokenId
{
    /**
     * @param string $value
     *
     * @return RefreshTokenId
     */
    public static function create(string $value): self
    {
        return new self($value);
    }
}
