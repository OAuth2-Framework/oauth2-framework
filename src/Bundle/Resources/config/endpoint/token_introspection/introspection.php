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
use OAuth2Framework\Component\Middleware;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('token_introspection_pipe')
        ->class(Middleware\Pipe::class)
        ->args([
            ref(Middleware\OAuth2ResponseMiddleware::class),
            //ref(FormPostBodyParserMiddleware::class),
            ref(\OAuth2Framework\Component\TokenIntrospectionEndpoint\AuthenticationMiddleware::class),
            ref(\OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenIntrospectionEndpoint::class),
        ]);

    $container->set(\OAuth2Framework\Component\TokenIntrospectionEndpoint\AuthenticationMiddleware::class)
        ->args([
            ref('oauth2_server.resource_server_repository'),
            ref(TokenIntrospectionEndpointAuthenticationMethodManager::class),
        ]);
    $container->set(\OAuth2Framework\Component\TokenIntrospectionEndpoint\AuthenticationMiddleware::class);

    $container->set(\OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenIntrospectionEndpoint::class)
        ->args([
            ref(\OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHintManager::class),
            ref('httplug.message_factory'),
        ]);
};
