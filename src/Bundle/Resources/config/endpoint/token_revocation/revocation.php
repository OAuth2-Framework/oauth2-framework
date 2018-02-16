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
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHintManager;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationGetEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationPostEndpoint;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('token_revocation_pipe')
        ->class(Middleware\Pipe::class)
        ->args([[
            ref(Middleware\OAuth2ResponseMiddleware::class),
            ref(\OAuth2Framework\Component\ClientAuthentication\ClientAuthenticationMiddleware::class),
            ref('token_revocation_method_handler'),
        ]])
        ->tag('controller.service_arguments');

    $container->set('token_revocation_method_handler')
        ->class(\OAuth2Framework\Component\Middleware\HttpMethodMiddleware::class)
        ->call('add', ['POST', ref(TokenRevocationPostEndpoint::class)])
        ->call('add', ['GET', ref(TokenRevocationGetEndpoint::class)]);

    $container->set(TokenTypeHintManager::class);

    $container->set(TokenRevocationPostEndpoint::class)
        ->args([
            ref(TokenTypeHintManager::class),
            ref('httplug.message_factory'),
        ]);

    $container->set(TokenRevocationGetEndpoint::class)
        ->args([
            ref(TokenTypeHintManager::class),
            ref('httplug.message_factory'),
            '%oauth2_server.endpoint.token_revocation.allow_callback%',
        ]);
};
