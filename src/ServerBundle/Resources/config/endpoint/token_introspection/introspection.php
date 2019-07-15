<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\Core\AccessToken\AccessTokenIntrospectionTypeHint;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Middleware;
use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMiddleware;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenIntrospectionEndpoint;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHintManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set('token_introspection_pipe')
        ->class(Middleware\Pipe::class)
        ->args([
            ref('oauth2_server.message_middleware.for_client_authentication'),
            ref(AuthenticationMiddleware::class),
            ref(TokenIntrospectionEndpoint::class),
        ])
        ->tag('controller.service_arguments')
    ;

    $container->set(TokenTypeHintManager::class);

    $container->set(TokenIntrospectionEndpoint::class)
        ->args([
            ref(TokenTypeHintManager::class),
            ref(\Psr\Http\Message\ResponseFactoryInterface::class),
        ])
    ;

    $container->set(AccessTokenIntrospectionTypeHint::class)
        ->args([
            ref(AccessTokenRepository::class),
        ])
    ;
};
