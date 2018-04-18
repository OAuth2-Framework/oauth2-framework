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
use OAuth2Framework\Component\Core\Message;
use OAuth2Framework\ServerBundle\Middleware;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $container->set('oauth2_message_middleware_with_client_authentication')
        ->class(Middleware\OAuth2MessageMiddleware::class)
        ->args([
            ref('oauth2_message_factory_manager_with_client_authentication'),
        ])
    ;
    $container->set('oauth2_message_factory_manager_with_client_authentication')
        ->class(Message\OAuth2MessageFactoryManager::class)
        ->call('addFactory', [ref(Message\Factory\AuthenticateResponseForClientFactory::class)])
        ->call('addFactory', [ref(Message\Factory\AccessDeniedResponseFactory::class)])
        ->call('addFactory', [ref(Message\Factory\BadRequestResponseFactory::class)])
        ->call('addFactory', [ref(Message\Factory\MethodNotAllowedResponseFactory::class)])
        ->call('addFactory', [ref(Message\Factory\NotImplementedResponseFactory::class)])
        ->call('addFactory', [ref(Message\Factory\RedirectResponseFactory::class)])
    ;

    $container->set('oauth2_message_middleware_with_token_authentication')
        ->class(Middleware\OAuth2MessageMiddleware::class)
        ->args([
            ref('oauth2_message_factory_manager_with_token_authentication'),
        ])
    ;
    $container->set('oauth2_message_factory_manager_with_token_authentication')
        ->class(Message\OAuth2MessageFactoryManager::class)
        ->call('addFactory', [ref(Message\Factory\AuthenticateResponseForTokenFactory::class)])
        ->call('addFactory', [ref(Message\Factory\AccessDeniedResponseFactory::class)])
        ->call('addFactory', [ref(Message\Factory\BadRequestResponseFactory::class)])
        ->call('addFactory', [ref(Message\Factory\MethodNotAllowedResponseFactory::class)])
        ->call('addFactory', [ref(Message\Factory\NotImplementedResponseFactory::class)])
        ->call('addFactory', [ref(Message\Factory\RedirectResponseFactory::class)])
    ;

    //Factories
    $container->set(Message\Factory\AccessDeniedResponseFactory::class);
    $container->set(Message\Factory\AuthenticateResponseForTokenFactory::class);
    $container->set(Message\Factory\AuthenticateResponseForClientFactory::class);
    $container->set(Message\Factory\BadRequestResponseFactory::class);
    $container->set(Message\Factory\MethodNotAllowedResponseFactory::class);
    $container->set(Message\Factory\NotImplementedResponseFactory::class);
    $container->set(Message\Factory\RedirectResponseFactory::class);
};
