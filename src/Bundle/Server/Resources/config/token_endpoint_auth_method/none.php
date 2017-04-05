<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\Server\TokenEndpointAuthMethod\None;
use function Fluent\create;

return [
    None::class => create()
        ->tag('oauth2_server_token_endpoint_auth_method'),
];
