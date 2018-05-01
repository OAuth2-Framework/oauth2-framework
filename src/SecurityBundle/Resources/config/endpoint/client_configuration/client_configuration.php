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
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientConfigurationEndpoint\ClientConfigurationEndpoint;
use OAuth2Framework\SecurityBundle\Controller\ClientConfigurationMiddleware;
use OAuth2Framework\SecurityBundle\Middleware;
use OAuth2Framework\SecurityBundle\Rule\ClientConfigurationRouteRule;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private();

    $container->set('client_configuration_endpoint_pipe')
        ->class(Middleware\Pipe::class)
        ->args([[
            ref(Middleware\OAuth2MessageMiddleware::class), //FIXME: use a dedicated message factory for this endpoint
            ref('oauth2_server.client_configuration.middleware'),
            ref('oauth2_server.client_configuration.endpoint'),
        ]])
        ->tag('controller.service_arguments');

    $container->set('oauth2_server.client_configuration.bearer_token')
        ->class(BearerToken::class)
        ->args([
            '%oauth2_server.endpoint.client_configuration.realm%',
            true,  // Authorization Header
            false, // Request Body
            false, // Query String
        ]);

    $container->set('oauth2_server.client_configuration.endpoint')
        ->class(ClientConfigurationEndpoint::class)
        ->args([
            ref('oauth2_server.client.repository'),
            ref('oauth2_server.client_configuration.bearer_token'),
            ref(\Http\Message\ResponseFactory::class), //TODO: change the way the response factory is managed
            ref('oauth2_server.client_rule.manager'),
        ]);

    $container->set('oauth2_server.client_configuration.middleware')
        ->class(ClientConfigurationMiddleware::class)
        ->args([
            ref('oauth2_server.client.repository'),
        ]);

    $container->set(ClientConfigurationRouteRule::class)
        ->autoconfigure()
        ->args([
            ref('router'),
        ]);
};
