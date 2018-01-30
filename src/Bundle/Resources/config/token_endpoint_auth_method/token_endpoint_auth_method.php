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

use OAuth2Framework\Bundle\Model\ClientRepository;
use OAuth2Framework\Component\Middleware\ClientAuthenticationMiddleware;
use OAuth2Framework\Component\TokenEndpointAuthMethod\TokenEndpointAuthMethodManager;
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
