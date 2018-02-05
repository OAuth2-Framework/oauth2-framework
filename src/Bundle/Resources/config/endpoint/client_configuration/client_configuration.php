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
use OAuth2Framework\Component\TokenEndpoint;
use OAuth2Framework\Component\TokenType\TokenTypeMiddleware;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('client_configuration_endpoint_pipe')
        ->class(Middleware\Pipe::class)
        ->args([
            ref(Middleware\OAuth2ResponseMiddleware::class),
            ref(Middleware\JsonBodyParserMiddleware::class),
            ref(\OAuth2Framework\Bundle\Controller\ClientConfigurationMiddleware::class),
            ref(\OAuth2Framework\Component\ClientConfigurationEndpoint\ClientConfigurationEndpoint::class),
        ]);
};

/*return [
    'client_configuration_bearer_token' => create(BearerToken::class)
        ->arguments(
            '%oauth2_server.endpoint.client_configuration.realm%',
            '%oauth2_server.endpoint.client_configuration.authorization_header%',
            '%oauth2_server.endpoint.client_configuration.request_body%',
            '%oauth2_server.endpoint.client_configuration.query_string%'
        ),

    ClientConfigurationMiddleware::class => create()
        ->arguments(
            get(ClientRepository::class)
        ),

    ClientConfigurationEndpoint::class => create()
        ->arguments(
            get('client_configuration_bearer_token'),
            get('command_bus'),
            get('httplug.message_factory')
        ),
];
*/