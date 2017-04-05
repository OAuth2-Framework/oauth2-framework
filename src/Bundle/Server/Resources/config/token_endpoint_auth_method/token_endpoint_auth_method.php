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

use OAuth2Framework\Bundle\Server\Model\ClientRepository;
use OAuth2Framework\Component\Server\Middleware\ClientAuthenticationMiddleware;
use OAuth2Framework\Component\Server\TokenEndpointAuthMethod\TokenEndpointAuthMethodManager;
use function Fluent\create;
use function Fluent\get;

return [
    TokenEndpointAuthMethodManager::class => create(),

    ClientAuthenticationMiddleware::class => create()
        ->arguments(
            get(ClientRepository::class),
            get(TokenEndpointAuthMethodManager::class),
            false
        ),
];
