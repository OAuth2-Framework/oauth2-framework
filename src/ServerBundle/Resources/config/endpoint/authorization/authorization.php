<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

use OAuth2Framework\Component\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestStorage;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message;
use OAuth2Framework\Component\Core\Middleware;
use OAuth2Framework\ServerBundle\Controller;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Templating\EngineInterface;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(AuthorizationEndpoint\Middleware\AuthorizationExceptionMiddleware::class);

    // Controllers and pipes
    $container->set(Controller\AuthorizationEndpointController::class)
        ->args([
            ref(ResponseFactoryInterface::class),
            ref(AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader::class),
            ref(ParameterChecker\ParameterCheckerManager::class),
            ref(AuthorizationEndpoint\User\UserAccountDiscovery::class),
            ref(AuthorizationEndpoint\Consent\ConsentRepository::class)->nullOnInvalid(),
            ref(ExtensionManager::class),
            ref(AuthorizationRequestStorage::class),
            ref(SessionInterface::class),
            ref(EngineInterface::class),
            '%oauth2_server.endpoint.authorization.login_template%',
            '%oauth2_server.endpoint.authorization.consent_template%',
        ]);

    $container->set('authorization_endpoint_pipe')
        ->class(Middleware\Pipe::class)
        ->args([[
            ref('oauth2_server.message_middleware.for_authorization_endpoint'),
            ref(AuthorizationEndpoint\Middleware\AuthorizationExceptionMiddleware::class),
            ref(Controller\AuthorizationEndpointController::class),
        ]])
        ->tag('controller.service_arguments');

    $container->set(AuthorizationEndpoint\User\UserAuthenticationCheckerManager::class);

    //Authorization Request Loader
    $container->set(AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader::class)
        ->args([
            ref(ClientRepository::class),
        ]);

    // Consent Screen Extension
    $container->set(AuthorizationEndpoint\Extension\ExtensionManager::class);

    // Parameter Checker
    $container->set(ParameterChecker\ParameterCheckerManager::class);

    $container->set(ParameterChecker\RedirectUriParameterChecker::class)
        ->tag('oauth2_server_authorization_parameter_checker');
    $container->set(ParameterChecker\DisplayParameterChecker::class);
    $container->set(ParameterChecker\PromptParameterChecker::class);
    $container->set(ParameterChecker\StateParameterChecker::class)
        ->args([
            '%oauth2_server.endpoint.authorization.enforce_state%',
        ]);

    // Rules
    $container->set(AuthorizationEndpoint\Rule\RequestUriRule::class);
    $container->set(AuthorizationEndpoint\Rule\ResponseTypesRule::class)
        ->args([
            ref(AuthorizationEndpoint\ResponseType\ResponseTypeManager::class),
        ]);

    $container->set('oauth2_server.message_middleware.for_authorization_endpoint')
        ->class(Middleware\OAuth2MessageMiddleware::class)
        ->args([
            ref('oauth2_server.message_factory_manager.for_authorization_endpoint'),
        ]);

    $container->set('oauth2_server.message_factory_manager.for_authorization_endpoint')
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
