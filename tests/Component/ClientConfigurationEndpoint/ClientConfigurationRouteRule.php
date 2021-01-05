<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Tests\Component\ClientConfigurationEndpoint;

use OAuth2Framework\Component\ClientConfigurationEndpoint\Rule\ClientConfigurationRouteRule as Base;
use OAuth2Framework\Component\Core\Client\ClientId;

class ClientConfigurationRouteRule extends Base
{
    protected function getRegistrationClientUri(ClientId $clientId): string
    {
        return \Safe\sprintf('https://www.example.com/client/%s', $clientId->getValue());
    }

    protected function generateRegistrationAccessToken(): string
    {
        return base64_encode(random_bytes(16));
    }
}
