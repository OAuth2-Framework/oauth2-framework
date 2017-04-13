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

use OAuth2Framework\Component\Server\Endpoint\TokenIntrospection\TokenIntrospectionEndpoint;
use OAuth2Framework\Component\Server\Middleware;
use OAuth2Framework\Component\Server\TokenIntrospectionEndpointAuthMethod\TokenIntrospectionEndpointAuthMethodManager;
use function Fluent\autowire;
use function Fluent\create;
use function Fluent\get;

return [
    TokenIntrospectionEndpoint::class => autowire(),

    Middleware\ResourceServerAuthenticationMiddleware::class => autowire(),

    TokenIntrospectionEndpointAuthMethodManager::class => create(),

    'token_introspection_pipe' => create(Middleware\Pipe::class)
        ->arguments([
            get(Middleware\OAuth2ResponseMiddleware::class),
            get(Middleware\ResourceServerAuthenticationMiddleware::class),
            get(TokenIntrospectionEndpoint::class),
        ]),
];
