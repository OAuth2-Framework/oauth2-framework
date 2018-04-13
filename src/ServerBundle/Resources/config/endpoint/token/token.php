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
use OAuth2Framework\Component\ClientAuthentication\ClientAuthenticationMiddleware;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('token_endpoint_pipe')
        ->class(Middleware\Pipe::class)
        ->args([[
            ref(Middleware\OAuth2ResponseMiddleware::class),
            ref(ClientAuthenticationMiddleware::class),
            ref(TokenEndpoint\GrantTypeMiddleware::class),
            ref(TokenTypeMiddleware::class),
            ref(TokenEndpoint\TokenEndpoint::class),
        ]])
        ->tag('controller.service_arguments');

    $container->set(TokenEndpoint\GrantTypeMiddleware::class)
        ->args([
            ref(TokenEndpoint\GrantTypeManager::class),
        ]);

    $container->set(TokenEndpoint\Extension\TokenEndpointExtensionManager::class);

    $container->set(TokenEndpoint\TokenEndpoint::class)
        ->args([
            ref('oauth2_server.client_repository'),
            ref('oauth2_server.user_account_repository'),
            ref(TokenEndpoint\Extension\TokenEndpointExtensionManager::class),
            ref('httplug.message_factory'),
            ref('oauth2_server.access_token_repository'),
            ref('oauth2_server.access_token_id_generator'),
            '%oauth2_server.access_token_lifetime%',
        ]);
};
