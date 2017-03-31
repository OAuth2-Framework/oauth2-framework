<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Interop\Http\Factory\ResponseFactoryInterface;
use OAuth2Framework\Bundle\Server\Controller\AuthorizationEndpointController;
use OAuth2Framework\Bundle\Server\Form\FormFactory;
use OAuth2Framework\Bundle\Server\Form\Handler\AuthorizationFormHandler;
use OAuth2Framework\Bundle\Server\Form\Type\AuthorizationType;
use OAuth2Framework\Bundle\Server\Model\ClientRepository;
use OAuth2Framework\Bundle\Server\Model\ScopeRepository;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AfterConsentScreen\AfterConsentScreenManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AuthorizationFactory;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AuthorizationRequestLoader;
use OAuth2Framework\Component\Server\Endpoint\Authorization\BeforeConsentScreen\BeforeConsentScreenManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\UserAccountDiscoveryManager;
use OAuth2Framework\Component\Server\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use function Fluent\create;
use function Fluent\get;

return [
    FormFactory::class => create()
        ->arguments(
            get('translator'),
            get('form.factory'),
            'oauth2_server_authorization_form', //'%oauth2_server.authorization_endpoint.name%',
            AuthorizationType::class, //'%oauth2_server.authorization_endpoint.type%',
            ['Authorize', 'Default']//'%oauth2_server.authorization_endpoint.validation_groups%'
        ),

    AuthorizationFormHandler::class => create(),

    AuthorizationEndpointController::class => create()
        ->arguments(
            get('templating'),
            '%oauth2_server.endpoint.authorization.template%',
            get(FormFactory::class),
            get(AuthorizationFormHandler::class),
            get('translator'),
            get('router'),
            '%oauth2_server.endpoint.authorization.login_route_name%',
            '%oauth2_server.endpoint.authorization.login_route_parameters%',
            get(ResponseFactoryInterface::class),
            get('session'),
            get(AuthorizationFactory::class),
            get(UserAccountDiscoveryManager::class),
            get(BeforeConsentScreenManager::class),
            get(AfterConsentScreenManager::class)
        ),

    'authorization_endpoint_pipe' => create(\OAuth2Framework\Component\Server\Middleware\Pipe::class)
        ->arguments([
            get(\OAuth2Framework\Component\Server\Middleware\OAuth2ResponseMiddleware::class),
            get(\OAuth2Framework\Component\Server\Middleware\TokenTypeMiddleware::class),
            get(AuthorizationEndpointController::class),
        ]),

    AuthorizationFactory::class => create()
        ->arguments(
            get(AuthorizationRequestLoader::class),
            get(ParameterCheckerManager::class)
        ),

    AuthorizationRequestLoader::class => create()
        ->arguments(
            get(ClientRepository::class)
        ),

    ParameterCheckerManager::class => create(),

    ParameterChecker\ResponseTypeAndResponseModeParameterChecker::class => create()
        ->arguments(
            get(ResponseTypeManager::class),
            get(ResponseModeManager::class),
            true
        )
        ->tag('oauth2_server_authorization_parameter_checker'),

    ParameterChecker\RedirectUriParameterChecker::class => create()
        ->arguments(
            true,
            true
        )
        ->tag('oauth2_server_authorization_parameter_checker'),

    ParameterChecker\DisplayParameterChecker::class => create()
        ->tag('oauth2_server_authorization_parameter_checker'),

    ParameterChecker\NonceParameterChecker::class => create()
        ->tag('oauth2_server_authorization_parameter_checker'),

    ParameterChecker\PromptParameterChecker::class => create()
        ->tag('oauth2_server_authorization_parameter_checker'),

    ParameterChecker\ScopeParameterChecker::class => create()
        ->arguments(
            get(ScopeRepository::class)
        )
        ->tag('oauth2_server_authorization_parameter_checker'),

    ParameterChecker\StateParameterChecker::class => create()
        ->arguments(
            true
        )
        ->tag('oauth2_server_authorization_parameter_checker'),

    ParameterChecker\TokenTypeParameterChecker::class => create()
        ->arguments(
            get(TokenTypeManager::class),
            true
        )
        ->tag('oauth2_server_authorization_parameter_checker'),

    UserAccountDiscoveryManager::class => create(),
    BeforeConsentScreenManager::class => create(),
    AfterConsentScreenManager::class => create(),
];
