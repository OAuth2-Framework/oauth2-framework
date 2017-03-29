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
use Interop\Http\Factory\ResponseFactoryInterface;
use OAuth2Framework\Component\Server\Endpoint\Token\Processor\ProcessorManager;
use OAuth2Framework\Component\Server\Endpoint\Token\TokenEndpoint;
use OAuth2Framework\Component\Server\Endpoint\Token\TokenEndpointExtensionManager;
use OAuth2Framework\Component\Server\GrantType\GrantTypeManager;
use OAuth2Framework\Component\Server\Middleware\ClientAuthenticationMiddleware;
use OAuth2Framework\Component\Server\Middleware\GrantTypeMiddleware;
use OAuth2Framework\Component\Server\Middleware\OAuth2ResponseMiddleware;
use OAuth2Framework\Component\Server\Middleware\Pipe;
use OAuth2Framework\Component\Server\Middleware\TokenTypeMiddleware;
use OAuth2Framework\Bundle\Server\Model\ScopeRepository;
use function Fluent\create;
use function Fluent\get;

return [
    'token_endpoint_pipe' => create(Pipe::class)
        ->arguments([
            get(OAuth2ResponseMiddleware::class),
            get(ClientAuthenticationMiddleware::class),
            get(GrantTypeMiddleware::class),
            get(TokenTypeMiddleware::class),
            get(TokenEndpoint::class),
        ]),

    ProcessorManager::class => create()
        ->arguments(
            get(ScopeRepository::class)
        ),

    TokenEndpointExtensionManager::class => create(),

    TokenEndpoint::class => create()
        ->arguments(
            get(ProcessorManager::class),
            get(ClientRepository::class),
            get('oauth2_server.user_account.repository'),
            get(TokenEndpointExtensionManager::class),
            get(ResponseFactoryInterface::class),
            get('command_bus')
        ),

    GrantTypeMiddleware::class => create()
        ->arguments(
            get(GrantTypeManager::class)
        ),
];
