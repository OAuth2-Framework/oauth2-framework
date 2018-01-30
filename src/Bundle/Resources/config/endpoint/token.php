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
use OAuth2Framework\Component\Endpoint\Token\Processor\ProcessorManager;
use OAuth2Framework\Component\Endpoint\Token\TokenEndpoint;
use OAuth2Framework\Component\Endpoint\Token\TokenEndpointExtensionManager;
use OAuth2Framework\Component\GrantType\GrantTypeManager;
use OAuth2Framework\Component\Middleware\ClientAuthenticationMiddleware;
use OAuth2Framework\Component\Middleware\GrantTypeMiddleware;
use OAuth2Framework\Component\Middleware\OAuth2ResponseMiddleware;
use OAuth2Framework\Component\Middleware\Pipe;
use OAuth2Framework\Component\Middleware\TokenTypeMiddleware;
use OAuth2Framework\Component\Model\RefreshToken\RefreshTokenRepositoryInterface;
use OAuth2Framework\Component\Model\Scope\ScopeRepositoryInterface;
use OAuth2Framework\Component\Model\Scope\ScopePolicyManager;
use function Fluent\create;
use function Fluent\get;
use OAuth2Framework\Component\Middleware\FormPostBodyParserMiddleware;

return [
    'token_endpoint_pipe' => create(Pipe::class)
        ->arguments([
            get(OAuth2ResponseMiddleware::class),
            get(FormPostBodyParserMiddleware::class),
            get(ClientAuthenticationMiddleware::class),
            get(GrantTypeMiddleware::class),
            get(TokenTypeMiddleware::class),
            get(TokenEndpoint::class),
        ]),

    ProcessorManager::class => create()
        ->arguments(
            get(ScopeRepositoryInterface::class)->nullIfMissing(),
            get(ScopePolicyManager::class)->nullIfMissing()
        ),

    TokenEndpointExtensionManager::class => create(),

    TokenEndpoint::class => create()
        ->arguments(
            get(ProcessorManager::class),
            get(ClientRepository::class),
            get('oauth2_server.user_account.repository'),
            get(TokenEndpointExtensionManager::class),
            get('httplug.message_factory'),
            get('oauth2_server.access_token.repository'),
            get(RefreshTokenRepositoryInterface::class)
        ),

    GrantTypeMiddleware::class => create()
        ->arguments(
            get(GrantTypeManager::class)
        ),
];