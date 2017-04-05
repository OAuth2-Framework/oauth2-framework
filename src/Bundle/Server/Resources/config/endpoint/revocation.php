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
use OAuth2Framework\Component\Server\Endpoint\TokenRevocation\TokenRevocationGetEndpoint;
use OAuth2Framework\Component\Server\Endpoint\TokenRevocation\TokenRevocationPostEndpoint;
use OAuth2Framework\Component\Server\Middleware;
use OAuth2Framework\Component\Server\Middleware\ClientAuthenticationMiddleware;
use OAuth2Framework\Component\Server\TokenEndpointAuthMethod\TokenEndpointAuthMethodManager;
use OAuth2Framework\Component\Server\TokenTypeHint\TokenTypeHintManager;
use function Fluent\create;
use function Fluent\get;

return [
    TokenRevocationGetEndpoint::class => create()
        ->arguments(
            get(TokenTypeHintManager::class),
            get('oauth2_server.http.response_factory'),
            '%oauth2_server.endpoint.token_revocation.allow_callback%'
        ),

    TokenRevocationPostEndpoint::class => create()
        ->arguments(
            get(TokenTypeHintManager::class),
            get('oauth2_server.http.response_factory')
        ),

    'token_revocation_method_handler' => create(Middleware\HttpMethod::class)
        ->method('addMiddleware', 'POST', get(TokenRevocationPostEndpoint::class))
        ->method('addMiddleware', 'GET', get(TokenRevocationGetEndpoint::class)),

    'token_revocation_pipe' => create(Middleware\Pipe::class)
        ->arguments([
            get(Middleware\OAuth2ResponseMiddleware::class),
            get('token_revocation_endpoint_client_authentication_middleware'),
            get('token_revocation_method_handler'),
        ]),

    'token_revocation_endpoint_client_authentication_middleware' => create(ClientAuthenticationMiddleware::class)
        ->arguments(
            get(ClientRepository::class),
            get(TokenEndpointAuthMethodManager::class),
            true
        ),
];
