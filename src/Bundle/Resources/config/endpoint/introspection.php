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

use OAuth2Framework\Component\Endpoint\TokenIntrospection\TokenIntrospectionEndpoint;
use OAuth2Framework\Component\Middleware;
use OAuth2Framework\Component\TokenIntrospectionEndpointAuthMethod\TokenIntrospectionEndpointAuthMethodManager;
use OAuth2Framework\Component\TokenTypeHint\TokenTypeHintManager;
use function Fluent\create;
use function Fluent\get;

return [
    TokenIntrospectionEndpoint::class => create()
        ->arguments(
            get(TokenTypeHintManager::class),
            get('httplug.message_factory')
        ),

    Middleware\ResourceServerAuthenticationMiddleware::class => create()
        ->arguments(
            get('oauth2_server.resource_server.repository'),
            get(TokenIntrospectionEndpointAuthMethodManager::class)
        ),

    TokenIntrospectionEndpointAuthMethodManager::class => create(),

    'token_introspection_pipe' => create(Middleware\Pipe::class)
        ->arguments([
            get(Middleware\OAuth2ResponseMiddleware::class),
            get(Middleware\FormPostBodyParserMiddleware::class),
            get(Middleware\ResourceServerAuthenticationMiddleware::class),
            get(TokenIntrospectionEndpoint::class),
        ]),
];
