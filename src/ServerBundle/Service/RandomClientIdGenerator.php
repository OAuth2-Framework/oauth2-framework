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

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientIdGenerator;

final class RandomClientIdGenerator implements ClientIdGenerator
{
    /**
     * {@inheritdoc}
     */
    public function createClientId(): ClientId
    {
        $value = \bin2hex(\random_bytes(32));

        return new ClientId($value);
    }
}
