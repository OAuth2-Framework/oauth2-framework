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

use OAuth2Framework\Component\Middleware\TokenTypeMiddleware;
use OAuth2Framework\Component\TokenType\TokenTypeManager;
use function Fluent\create;
use function Fluent\get;

return [
    TokenTypeMiddleware::class => create()
        ->arguments(
            get(TokenTypeManager::class),
            'oauth2_server.token_type.allow_token_type_parameter'
        ),

    TokenTypeManager::class => create(),
];
