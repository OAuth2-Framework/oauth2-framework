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
use function Fluent\create;
use function Fluent\autowire;

return [
    OAuth2ResponseMiddleware::class => autowire(),

    OAuth2ResponseFactoryManager::class => autowire(),

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
    AuthenticateResponseFactory::class => autowire()
        ->tag('oauth2_server_response_factory'),
];
