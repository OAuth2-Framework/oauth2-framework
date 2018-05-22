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
use OAuth2Framework\ServerBundle\Controller\AuthorizationEndpointController;
use OAuth2Framework\ServerBundle\Form\FormFactory;
use OAuth2Framework\ServerBundle\Form\Handler\AuthorizationFormHandler;
use OAuth2Framework\Component\Core\Message;
use OAuth2Framework\Component\Core\Middleware;
use OAuth2Framework\Component\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;
use OAuth2Framework\Component\Core\TokenType\TokenTypeMiddleware;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(FormFactory::class)
        ->args([
            ref('form.factory'),
            '%oauth2_server.endpoint.authorization.form%',
            '%oauth2_server.endpoint.authorization.type%',
        ]);

    $container->set(AuthorizationFormHandler::class);
    $container->set(AuthorizationEndpoint\Middleware\AuthorizationExceptionMiddleware::class);

    $container->set(AuthorizationEndpointController::class)
        ->args([
            ref('templating'),
            '%oauth2_server.endpoint.authorization.template%',
            ref(FormFactory::class),
            ref(AuthorizationFormHandler::class),
            ref('translator'),
            ref('router'),
            '%oauth2_server.endpoint.authorization.login_route_name%',
            '%oauth2_server.endpoint.authorization.login_route_parameters%',
            ref(\Http\Message\ResponseFactory::class),
            ref('session'),
            ref(AuthorizationEndpoint\AuthorizationRequestLoader::class),
            ref(AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager::class),
            ref(AuthorizationEndpoint\UserAccount\UserAccountDiscovery::class),
            ref(AuthorizationEndpoint\UserAccount\UserAccountCheckerManager::class),
            ref(AuthorizationEndpoint\ConsentScreen\ExtensionManager::class),
        ]);

    $container->set(AuthorizationEndpoint\UserAccount\UserAccountCheckerManager::class)
        ->args([
        ]);

    $container->set(\OAuth2Framework\ServerBundle\Service\SymfonyUserDiscovery::class)
        ->args([
            ref('security.token_storage'),
            ref('security.authorization_checker'),
        ]);

    $container->set('authorization_endpoint_pipe')
        ->class(Middleware\Pipe::class)
        ->args([[
            ref('oauth2_server.message_middleware.for_authorization_endpoint'),
            ref(AuthorizationEndpoint\Middleware\AuthorizationExceptionMiddleware::class),
            ref(TokenTypeMiddleware::class),
            ref(AuthorizationEndpointController::class),
        ]])
        ->tag('controller.service_arguments');

    $container->set(AuthorizationEndpoint\AuthorizationRequestLoader::class)
        ->args([
            ref('oauth2_server.client.repository'),
        ]);

    // Consent Screen Extension
    $container->set(AuthorizationEndpoint\ConsentScreen\ExtensionManager::class);

    // Response Type
    //$container->set(AuthorizationEndpoint\ResponseTypeManager::class);

    // Parameter Checker
    $container->set(ParameterChecker\ParameterCheckerManager::class);

    $container->set(ParameterChecker\RedirectUriParameterChecker::class)
        ->tag('oauth2_server_authorization_parameter_checker');
    $container->set(ParameterChecker\DisplayParameterChecker::class);
    //FIXME $container->set(ParameterChecker\NonceParameterChecker::class);
    $container->set(ParameterChecker\PromptParameterChecker::class);
    $container->set(ParameterChecker\StateParameterChecker::class)
        ->args([
            '%oauth2_server.endpoint.authorization.enforce_state%',
        ]);
    //$container->set(\OAuth2Framework\Component\Core\TokenType\TokenTypeParameterChecker::class);

    // Rules
    $container->set(AuthorizationEndpoint\Rule\RequestUriRule::class);
    $container->set(AuthorizationEndpoint\Rule\ResponseTypesRule::class)
        ->args([
            ref(AuthorizationEndpoint\ResponseTypeManager::class),
        ]);
    //FIXME $container->set(AuthorizationEndpoint\Rule\SectorIdentifierUriRule::class);

    $container->set('oauth2_server.message_middleware.for_authorization_endpoint')
        ->class(Middleware\OAuth2MessageMiddleware::class)
        ->args([
            ref('oauth2_server.message_factory_manager.for_authorization_endpoint'),
        ]);

    $container->set('oauth2_server.message_factory_manager.for_authorization_endpoint')
        ->class(Message\OAuth2MessageFactoryManager::class)
        ->args([
            ref(\Http\Message\ResponseFactory::class),
        ])
        ->call('addFactory', [ref('oauth2_server.message_factory.302')])
        ->call('addFactory', [ref('oauth2_server.message_factory.400')])
        ->call('addFactory', [ref('oauth2_server.message_factory.403')])
        ->call('addFactory', [ref('oauth2_server.message_factory.405')])
        ->call('addFactory', [ref('oauth2_server.message_factory.501')])
        ;
};
