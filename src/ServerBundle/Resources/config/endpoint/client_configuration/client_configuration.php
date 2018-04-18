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
use OAuth2Framework\ServerBundle\Rule\ClientConfigurationRouteRule;
use OAuth2Framework\ServerBundle\Middleware;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('client_configuration_endpoint_pipe')
        ->class(Middleware\Pipe::class)
        ->args([[
            //ref(Middleware\OAuth2MessageMiddleware::class),
            ref(\OAuth2Framework\ServerBundle\Controller\ClientConfigurationMiddleware::class),
            ref(\OAuth2Framework\Component\ClientConfigurationEndpoint\ClientConfigurationEndpoint::class),
        ]])
        ->tag('controller.service_arguments');

    $container->set('client_configuration_bearer_token')
        ->class(\OAuth2Framework\Component\BearerTokenType\BearerToken::class)
        ->args([
            '%oauth2_server.endpoint.client_configuration.realm%',
            true,
            false,
            false,
        ]);

    $container->set(\OAuth2Framework\Component\ClientConfigurationEndpoint\ClientConfigurationEndpoint::class)
        ->args([
            ref(\OAuth2Framework\Component\Core\Client\ClientRepository::class),
            ref('client_configuration_bearer_token'),
            ref('command_bus'),
            ref('httplug.message_factory'),
            ref(\OAuth2Framework\Component\ClientRule\RuleManager::class),
        ]);

    $container->set(\OAuth2Framework\ServerBundle\Controller\ClientConfigurationMiddleware::class)
        ->args([
            ref(\OAuth2Framework\Component\Core\Client\ClientRepository::class),
        ]);

    $container->set(ClientConfigurationRouteRule::class)
        ->args([
            ref('router'),
        ]);
};
