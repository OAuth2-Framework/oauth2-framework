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

use OAuth2Framework\Bundle\TokenEndpointAuthMethod\ClientSecretBasic;
use function Fluent\create;

return [
    ClientSecretBasic::class => create()
        ->arguments(
            '%oauth2_server.token_endpoint_auth_method.client_secret_basic.realm%',
            '%oauth2_server.token_endpoint_auth_method.client_secret_basic.secret_lifetime%'
        )
        ->tag('oauth2_server_token_endpoint_auth_method'),
];
