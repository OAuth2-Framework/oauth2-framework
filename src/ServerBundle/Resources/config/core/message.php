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

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\Core\Message;
use OAuth2Framework\Component\Core\Middleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $container->set('oauth2_server.message_middleware.for_client_authentication')
        ->class(Middleware\OAuth2MessageMiddleware::class)
        ->args([
            ref('oauth2_server.message_factory_manager.for_client_authentication'),
        ]);
    $container->set('oauth2_server.message_factory_manager.for_client_authentication')
        ->class(Message\OAuth2MessageFactoryManager::class)
        ->args([
            ref(\Http\Message\ResponseFactory::class),
        ]);

    $container->set('oauth2_server.message_middleware.for_token_authentication')
        ->class(Middleware\OAuth2MessageMiddleware::class)
        ->args([
            ref('oauth2_server.message_factory_manager.for_token_authentication'),
        ]);
    $container->set('oauth2_server.message_factory_manager.for_token_authentication')
        ->class(Message\OAuth2MessageFactoryManager::class)
        ->args([
            ref(\Http\Message\ResponseFactory::class),
        ]);

    //Factories
    $container->set('oauth2_server.message_factory.403')
        ->class(Message\Factory\AccessDeniedResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication');

    $container->set('oauth2_server.message_factory.401_for_token')
        ->args([
            ref(\OAuth2Framework\Component\Core\TokenType\TokenTypeManager::class),
        ])
        ->class(Message\Factory\AuthenticateResponseForTokenFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication');

    $container->set('oauth2_server.message_factory.401_for_client')
        ->args([
            ref(AuthenticationMethodManager::class),
        ])
        ->class(Message\Factory\AuthenticateResponseForClientFactory::class)
        ->tag('oauth2_server_message_factory_for_client_authentication');

    $container->set('oauth2_server.message_factory.400')
        ->class(Message\Factory\BadRequestResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication');

    $container->set('oauth2_server.message_factory.405')
        ->class(Message\Factory\MethodNotAllowedResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication');

    $container->set('oauth2_server.message_factory.501')
        ->class(Message\Factory\NotImplementedResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication');

    $container->set('oauth2_server.message_factory.303')
        ->class(Message\Factory\RedirectResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication');
};
