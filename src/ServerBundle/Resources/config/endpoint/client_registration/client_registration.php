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

use OAuth2Framework\Component\ClientRegistrationEndpoint\ClientRegistrationEndpoint;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message;
use OAuth2Framework\Component\Core\Middleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set('client_registration_endpoint_pipe')
        ->class(Middleware\Pipe::class)
        ->args([[
            ref('oauth2_server.message_middleware.for_client_registration'),
            ref('oauth2_server.client_registration.endpoint'),
        ]])
        ->tag('controller.service_arguments')
    ;

    $container->set('oauth2_server.client_registration.endpoint')
        ->class(ClientRegistrationEndpoint::class)
        ->args([
            ref(ClientRepository::class),
            ref(ResponseFactoryInterface::class), //TODO
            ref(RuleManager::class),
        ])
    ;

    $container->set('oauth2_server.message_middleware.for_client_registration')
        ->class(Middleware\OAuth2MessageMiddleware::class)
        ->args([
            ref('oauth2_server.message_factory_manager.for_client_registration'),
        ])
    ;
    $container->set('oauth2_server.message_factory_manager.for_client_registration')
        ->class(Message\OAuth2MessageFactoryManager::class)
        ->args([
            ref(ResponseFactoryInterface::class),
        ])
        ->call('addFactory', [ref('oauth2_server.message_factory.303')])
        ->call('addFactory', [ref('oauth2_server.message_factory.400')])
        ->call('addFactory', [ref('oauth2_server.message_factory.403')])
        ->call('addFactory', [ref('oauth2_server.message_factory.405')])
        ->call('addFactory', [ref('oauth2_server.message_factory.501')])
    ;
};
