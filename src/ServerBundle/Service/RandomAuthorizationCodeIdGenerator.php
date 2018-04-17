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

namespace OAuth2Framework\ServerBundle\Service;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeIdGenerator;

final class RandomAuthorizationCodeIdGenerator implements AuthorizationCodeIdGenerator
{
    /**
     * {@inheritdoc}
     */
    public function createAuthorizationCodeId(): AuthorizationCodeId
    {
        $value = bin2hex(random_bytes(64));

        return AuthorizationCodeId::create($value);
    }
}
