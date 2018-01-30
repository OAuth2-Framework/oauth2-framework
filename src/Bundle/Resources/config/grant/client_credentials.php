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

use OAuth2Framework\Component\GrantType\ClientCredentialsGrantType;
use function Fluent\create;

return [
    ClientCredentialsGrantType::class => create()
        ->arguments(
            '%oauth2_server.grant.client_credentials.issue_refresh_token%'
        )
        ->tag('oauth2_server_grant_type'),
];
