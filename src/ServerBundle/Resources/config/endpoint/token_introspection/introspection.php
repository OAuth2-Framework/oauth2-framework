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

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use OAuth2Framework\ServerBundle\Middleware;
use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMiddleware;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenIntrospectionEndpoint;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHintManager;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('token_introspection_pipe')
        ->class(Middleware\Pipe::class)
        ->args([
            ref('oauth2_message_middleware_with_client_authentication'),
            ref(AuthenticationMiddleware::class),
            ref(TokenIntrospectionEndpoint::class),
        ])
        ->tag('controller.service_arguments');

    $container->set(TokenTypeHintManager::class);

    $container->set(TokenIntrospectionEndpoint::class)
        ->args([
            ref(TokenTypeHintManager::class),
            ref('httplug.message_factory'),
        ]);
};
