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
use OAuth2Framework\Component\TokenEndpoint;
use OAuth2Framework\Component\Core\TokenType\TokenTypeMiddleware;
use OAuth2Framework\Component\ClientAuthentication\ClientAuthenticationMiddleware;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('token_endpoint_pipe')
        ->class(Middleware\Pipe::class)
        ->args([[
            ref('oauth2_message_middleware_with_client_authentication'),
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
            ref(\OAuth2Framework\Component\Core\Client\ClientRepository::class),
            ref(\OAuth2Framework\Component\Core\UserAccount\UserAccountRepository::class),
            ref(TokenEndpoint\Extension\TokenEndpointExtensionManager::class),
            ref(\Http\Message\ResponseFactory::class),
            ref(\OAuth2Framework\Component\Core\AccessToken\AccessTokenIdGenerator::class),
            ref(\OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository::class),
            '%oauth2_server.access_token_lifetime%',
        ]);
};
