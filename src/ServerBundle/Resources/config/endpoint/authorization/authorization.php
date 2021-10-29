<?php

declare(strict_types=1);

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationEndpoint;
/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestEntryEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestStorage;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\ConsentPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\LoginPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\NonePrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\SelectAccountPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\LoginHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\Middleware\AuthorizationExceptionMiddleware;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\DisplayParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\PromptParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\RedirectUriParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ResponseTypeParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\StateParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseModeGuesser;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseTypeGuesser;
use OAuth2Framework\Component\AuthorizationEndpoint\Rule\RequestUriRule;
use OAuth2Framework\Component\AuthorizationEndpoint\Rule\ResponseTypesRule;
use OAuth2Framework\Component\AuthorizationEndpoint\SelectAccountHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationCheckerManager;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use OAuth2Framework\Component\Core\Middleware\OAuth2MessageMiddleware;
use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\AuthorizationRequestHookCompilerPass;
use OAuth2Framework\ServerBundle\Controller\PipeController;
use OAuth2Framework\ServerBundle\Service\AuthorizationRequestSessionStorage;
use OAuth2Framework\ServerBundle\Service\IgnoreAccountSelectionHandler;
use OAuth2Framework\ServerBundle\Service\RedirectAuthorizationRequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(AuthorizationExceptionMiddleware::class)
        ->args([service(ResponseTypeGuesser::class), service(ResponseModeGuesser::class)])
    ;

    // Controllers and pipes
    $container->set(AuthorizationRequestEntryEndpoint::class)
        ->args([
            service(ParameterCheckerManager::class),
            service(AuthorizationRequestLoader::class),
            service(AuthorizationRequestStorage::class),
            service(AuthorizationRequestHandler::class),
            service(UserAccountDiscovery::class),
        ])
    ;

    $container->set('authorization_request_entry_pipe')
        ->class(Pipe::class)
        ->args([[
            service('oauth2_server.message_middleware.for_authorization_endpoint'),
            service(AuthorizationExceptionMiddleware::class),
            service(AuthorizationRequestEntryEndpoint::class),
        ]])
    ;

    $container->set('authorization_request_entry_endpoint_pipe')
        ->class(PipeController::class)
        ->args([service('authorization_request_entry_pipe')])
        ->tag('controller.service_arguments')
    ;

    $container->set(RedirectAuthorizationRequestHandler::class)
        ->args([service(RouterInterface::class), service(ResponseFactoryInterface::class)])
    ;

    $container->set(AuthorizationEndpoint::class)
        ->args([
            service(ResponseFactoryInterface::class),
            service(TokenTypeGuesser::class),
            service(ResponseTypeGuesser::class),
            service(ResponseModeGuesser::class),
            service(ConsentRepository::class)->nullOnInvalid(),
            service(ExtensionManager::class),
            service(AuthorizationRequestStorage::class),
            service(LoginHandler::class),
            service(ConsentHandler::class),
        ])
    ;

    $container->set('authorization_pipe')
        ->class(Pipe::class)
        ->args([[
            service('oauth2_server.message_middleware.for_authorization_endpoint'),
            service(AuthorizationExceptionMiddleware::class),
            service(AuthorizationEndpoint::class),
        ]])
    ;

    $container->set('authorization_endpoint_pipe')
        ->class(PipeController::class)
        ->args([service('authorization_pipe')])
        ->tag('controller.service_arguments')
    ;

    $container->set(UserAuthenticationCheckerManager::class);

    //Hooks
    $container->set(ConsentPrompt::class)
        ->args([service(ConsentHandler::class)])
        ->tag(AuthorizationRequestHookCompilerPass::TAG_NAME, [
            'priority' => -200,
        ])
    ;

    $container->set(LoginPrompt::class)
        ->args([service(UserAuthenticationCheckerManager::class), service(LoginHandler::class)])
        ->tag(AuthorizationRequestHookCompilerPass::TAG_NAME, [
            'priority' => -100,
        ])
    ;

    $container->set(NonePrompt::class)
        ->args([service(ConsentRepository::class)->nullOnInvalid()])
        ->tag(AuthorizationRequestHookCompilerPass::TAG_NAME, [
            'priority' => 0,
        ])
    ;

    $container->set(SelectAccountPrompt::class)
        ->args([service(SelectAccountHandler::class)])
        ->tag(AuthorizationRequestHookCompilerPass::TAG_NAME, [
            'priority' => 0,
        ])
    ;

    $container->set(AuthorizationRequestSessionStorage::class)
        ->args([service(SessionInterface::class)])
    ;
    $container->set(IgnoreAccountSelectionHandler::class);

    //Authorization Request Loader
    $container->set(AuthorizationRequestLoader::class)
        ->args([service(ClientRepository::class)])
    ;

    // Consent Screen Extension
    $container->set(ExtensionManager::class);

    // Parameter Checker
    $container->set(ParameterCheckerManager::class);

    $container->set(RedirectUriParameterChecker::class)
        ->tag('oauth2_server_authorization_parameter_checker')
    ;
    $container->set(DisplayParameterChecker::class);
    $container->set(PromptParameterChecker::class);
    $container->set(StateParameterChecker::class)
        ->args(['%oauth2_server.endpoint.authorization.enforce_state%'])
    ;

    // Rules
    $container->set(RequestUriRule::class);
    $container->set(ResponseTypesRule::class)
        ->args([service(ResponseTypeManager::class)])
    ;

    $container->set('oauth2_server.message_middleware.for_authorization_endpoint')
        ->class(OAuth2MessageMiddleware::class)
        ->args([service('oauth2_server.message_factory_manager.for_authorization_endpoint')])
    ;

    $container->set('oauth2_server.message_factory_manager.for_authorization_endpoint')
        ->class(OAuth2MessageFactoryManager::class)
        ->args([service(ResponseFactoryInterface::class)])
        ->call('addFactory', [service('oauth2_server.message_factory.303')])
        ->call('addFactory', [service('oauth2_server.message_factory.400')])
        ->call('addFactory', [service('oauth2_server.message_factory.403')])
        ->call('addFactory', [service('oauth2_server.message_factory.405')])
        ->call('addFactory', [service('oauth2_server.message_factory.501')])
        ;

    $container->set(ResponseModeGuesser::class)
        ->args([service(ResponseModeManager::class), false])
    ;

    $container->set(ResponseTypeGuesser::class)
        ->args([service(ResponseTypeManager::class)])
    ;

    $container->set(ResponseTypeParameterChecker::class)
        ->args([service(ResponseTypeManager::class)])
    ;
};
