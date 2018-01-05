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

use OAuth2Framework\Component\Server\TokenTypeHint\AccessTokenTypeHint;
use function Fluent\create;
use function Fluent\get;

return [
    AccessTokenTypeHint::class => create()
        ->arguments(
            get('oauth2_server.access_token.repository')
        )
        ->tag('oauth2_server_token_type_hint'),
];
