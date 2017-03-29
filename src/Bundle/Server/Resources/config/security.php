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

use function Fluent\create;
use function Fluent\get;
use OAuth2Framework\Component\Server\Middleware\OAuth2SecurityMiddleware;
use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Server\Security\AccessTokenHandlerManager;

return [
    OAuth2SecurityMiddleware::class => create()
        ->arguments(
            get(TokenTypeManager::class),
            get(AccessTokenHandlerManager::class),
            null, //Scope,
            [] // Additional Data
        ),
];
