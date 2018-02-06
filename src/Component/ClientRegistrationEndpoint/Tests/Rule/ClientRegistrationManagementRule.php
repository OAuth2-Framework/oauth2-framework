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

namespace OAuth2Framework\Component\ClientRegistrationEndpoint\Tests\Rule;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\ClientRegistrationEndpoint\Rule\ClientRegistrationManagementRule as Base;

class ClientRegistrationManagementRule extends Base
{
    /**
     * {@inheritdoc}
     */
    protected function getRegistrationClientUri(ClientId $clientId): string
    {
        return sprintf('https://www.example.com/client/%s', $clientId->getValue());
    }

    /**
     * {@inheritdoc}
     */
    protected function generateRegistrationAccessToken(): string
    {
        return base64_encode(random_bytes(16));
    }
}
