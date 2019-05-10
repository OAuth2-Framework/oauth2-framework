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

use Doctrine\Common\Annotations\Reader;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandlerManager;
use OAuth2Framework\Component\Core\Message;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\SecurityBundle\Annotation;
use OAuth2Framework\SecurityBundle\Resolver\AccessTokenResolver;
use OAuth2Framework\SecurityBundle\Security\Authentication\Provider\OAuth2Provider;
use OAuth2Framework\SecurityBundle\Security\EntryPoint\OAuth2EntryPoint;
use OAuth2Framework\SecurityBundle\Security\Firewall\OAuth2Listener;
use OAuth2Framework\SecurityBundle\Security\Handler\DefaultFailureHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $container->set('oauth2_security.access_token_handler_manager')
        ->class(AccessTokenHandlerManager::class);

    $container->set(OAuth2Provider::class);

    $container->set('oauth2_security.token_type_manager')
        ->class(TokenTypeManager::class);

    $container->set('oauth2_security.message_factory_manager')
        ->class(Message\OAuth2MessageFactoryManager::class)
        ->args([
            ref(ResponseFactoryInterface::class),
        ])
        ->call('addFactory', [ref('oauth2_security.message_factory.303')])
        ->call('addFactory', [ref('oauth2_security.message_factory.400')])
        ->call('addFactory', [ref('oauth2_security.message_factory.401')])
        ->call('addFactory', [ref('oauth2_security.message_factory.403')])
        ->call('addFactory', [ref('oauth2_security.message_factory.405')])
        ->call('addFactory', [ref('oauth2_security.message_factory.501')]);

    $container->set('oauth2_security.listener')
        ->class(OAuth2Listener::class)
        ->args([
            ref('oauth2_security.psr7_message_factory'),
            ref(TokenStorageInterface::class),
            ref(AuthenticationManagerInterface::class),
            ref('oauth2_security.token_type_manager'),
            ref('oauth2_security.access_token_handler_manager'),
            ref('oauth2_security.message_factory_manager'),
        ]);

    $container->set(DefaultFailureHandler::class)
        ->args([
            ref('oauth2_security.message_factory_manager'),
        ]);

    $container->set(OAuth2EntryPoint::class)
        ->args([
            ref('oauth2_security.message_factory_manager'),
        ]);

    $container->set(Annotation\AnnotationDriver::class)
        ->args([
            ref(Reader::class),
            ref(TokenStorageInterface::class),
            ref('oauth2_security.message_factory_manager'),
        ])
        ->tag('kernel.event_listener', [
            'event' => 'kernel.controller',
            'method' => 'onKernelController',
        ]);
    $container->set(Annotation\Checker\ClientIdChecker::class);
    $container->set(Annotation\Checker\ResourceOwnerIdChecker::class);
    $container->set(Annotation\Checker\ScopeChecker::class);
    $container->set(Annotation\Checker\TokenTypeChecker::class);

    $container->set('oauth2_security.message_factory.401')
        ->class(Message\Factory\AuthenticateResponseForTokenFactory::class)
        ->args([
            ref('oauth2_security.token_type_manager'),
        ])
        ->tag('oauth2_security_message_factory');

    $container->set('oauth2_security.message_factory.403')
        ->class(Message\Factory\AccessDeniedResponseFactory::class)
        ->tag('oauth2_security_message_factory');

    $container->set('oauth2_security.message_factory.400')
        ->class(Message\Factory\BadRequestResponseFactory::class)
        ->tag('oauth2_security_message_factory');

    $container->set('oauth2_security.message_factory.405')
        ->class(Message\Factory\MethodNotAllowedResponseFactory::class)
        ->tag('oauth2_security_message_factory');

    $container->set('oauth2_security.message_factory.501')
        ->class(Message\Factory\NotImplementedResponseFactory::class)
        ->tag('oauth2_security_message_factory');

    $container->set('oauth2_security.message_factory.303')
        ->class(Message\Factory\RedirectResponseFactory::class)
        ->tag('oauth2_security_message_factory');

    $container->set(AccessTokenResolver::class);
};
