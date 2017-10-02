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

use OAuth2Framework\Bundle\Server\Response\AuthenticateResponseFactory;
use OAuth2Framework\Component\Server\Middleware\OAuth2ResponseMiddleware;
use OAuth2Framework\Component\Server\Response\Factory;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\TokenEndpointAuthMethod\TokenEndpointAuthMethodManager;
use function Fluent\create;
use function Fluent\get;

return [
    OAuth2ResponseMiddleware::class => create()
        ->arguments(
            get(OAuth2ResponseFactoryManager::class)
        ),
    OAuth2ResponseFactoryManager::class => create()
        ->arguments(
            get('httplug.message_factory')
        ),
    Factory\AccessDeniedResponseFactory::class => create()
        ->tag('oauth2_server_response_factory'),
    Factory\BadRequestResponseFactory::class => create()
        ->tag('oauth2_server_response_factory'),
    Factory\MethodNotAllowedResponseFactory::class => create()
        ->tag('oauth2_server_response_factory'),
    Factory\NotImplementedResponseFactory::class => create()
        ->tag('oauth2_server_response_factory'),
    Factory\RedirectResponseFactory::class => create()
        ->tag('oauth2_server_response_factory'),
    AuthenticateResponseFactory::class => create()
        ->arguments(
            get(TokenEndpointAuthMethodManager::class)
        )
        ->tag('oauth2_server_response_factory'),
];
