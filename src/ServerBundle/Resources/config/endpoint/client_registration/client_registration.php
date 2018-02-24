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
use OAuth2Framework\Component\ClientRegistrationEndpoint\ClientRegistrationEndpoint;
use OAuth2Framework\Component\ClientRule\RuleManager;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('client_registration_endpoint_pipe')
        ->class(Middleware\Pipe::class)
        ->args([[
            ref(Middleware\OAuth2ResponseMiddleware::class),
            ref(ClientRegistrationEndpoint::class),
        ]])
        ->tag('controller.service_arguments');

    $container->set(ClientRegistrationEndpoint::class)
        ->args([
            ref('oauth2_server.client_repository'),
            ref('httplug.message_factory'),
            ref('command_bus'),
            ref(RuleManager::class),
        ]);
};
