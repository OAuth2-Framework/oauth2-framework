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

namespace OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization;

use OAuth2Framework\Component\Server\Model\Token\TokenId;

final class PreConfiguredAuthorizationId extends TokenId
{
    /**
     * @param string $value
     *
     * @return PreConfiguredAuthorizationId
     */
    public static function create(string $value): PreConfiguredAuthorizationId
    {
        return new self($value);
    }
}
