<?php

declare(strict_types=1);
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use OAuth2Framework\SecurityBundle\Annotation\AnnotationDriver;
use OAuth2Framework\SecurityBundle\Annotation\Checker\ClientIdChecker;
use OAuth2Framework\SecurityBundle\Annotation\Checker\ResourceOwnerIdChecker;
use OAuth2Framework\SecurityBundle\Annotation\Checker\ScopeChecker;
use OAuth2Framework\SecurityBundle\Annotation\Checker\TokenTypeChecker;
use OAuth2Framework\Component\Core\Message\Factory\AuthenticateResponseForTokenFactory;
use OAuth2Framework\Component\Core\Message\Factory\AccessDeniedResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\BadRequestResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\MethodNotAllowedResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\NotImplementedResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\RedirectResponseFactory;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Doctrine\Common\Annotations\Reader;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandlerManager;
use OAuth2Framework\Component\Core\Message;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\SecurityBundle\Annotation;
use OAuth2Framework\SecurityBundle\EventListener\RequestListener;
use OAuth2Framework\SecurityBundle\Resolver\AccessTokenResolver;
use OAuth2Framework\SecurityBundle\Security\Authentication\Provider\OAuth2Provider;
use OAuth2Framework\SecurityBundle\Security\EntryPoint\OAuth2EntryPoint;
use OAuth2Framework\SecurityBundle\Security\ExpressionLanguageProvider;
use OAuth2Framework\SecurityBundle\Security\Firewall\OAuth2Listener;
use OAuth2Framework\SecurityBundle\Security\Handler\DefaultFailureHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autowire()
        ->autoconfigure()
    ;

    $container->set('oauth2_security.access_token_handler_manager')
        ->class(AccessTokenHandlerManager::class)
    ;

    $container->set(OAuth2Provider::class);

    $container->set('oauth2_security.token_type_manager')
        ->class(TokenTypeManager::class)
    ;

    $container->set('oauth2_security.message_factory_manager')
        ->class(OAuth2MessageFactoryManager::class)
        ->args([
            ref(ResponseFactoryInterface::class),
        ])
        ->call('addFactory', [ref('oauth2_security.message_factory.303')])
        ->call('addFactory', [ref('oauth2_security.message_factory.400')])
        ->call('addFactory', [ref('oauth2_security.message_factory.401')])
        ->call('addFactory', [ref('oauth2_security.message_factory.403')])
        ->call('addFactory', [ref('oauth2_security.message_factory.405')])
        ->call('addFactory', [ref('oauth2_security.message_factory.501')])
    ;

    $container->set('oauth2_security.listener')
        ->class(OAuth2Listener::class)
        ->args([
            null,
            ref(TokenStorageInterface::class),
            ref(AuthenticationManagerInterface::class),
            ref('oauth2_security.token_type_manager'),
            ref('oauth2_security.access_token_handler_manager'),
            ref('oauth2_security.message_factory_manager'),
        ])
    ;

    $container->set(DefaultFailureHandler::class)
        ->args([
            ref('oauth2_security.message_factory_manager'),
        ])
    ;

    $container->set(OAuth2EntryPoint::class)
        ->args([
            ref('oauth2_security.message_factory_manager'),
        ])
    ;

    $container->set(AnnotationDriver::class)
        ->args([
            ref(Reader::class),
            ref(TokenStorageInterface::class),
            ref('oauth2_security.message_factory_manager'),
        ])
        ->tag('kernel.event_listener', [
            'event' => 'kernel.controller',
            'method' => 'onKernelController',
        ])
    ;
    $container->set(ClientIdChecker::class);
    $container->set(ResourceOwnerIdChecker::class);
    $container->set(ScopeChecker::class);
    $container->set(TokenTypeChecker::class);

    $container->set('oauth2_security.message_factory.401')
        ->class(AuthenticateResponseForTokenFactory::class)
        ->args([
            ref('oauth2_security.token_type_manager'),
        ])
        ->tag('oauth2_security_message_factory')
    ;

    $container->set('oauth2_security.message_factory.403')
        ->class(AccessDeniedResponseFactory::class)
        ->tag('oauth2_security_message_factory')
    ;

    $container->set('oauth2_security.message_factory.400')
        ->class(BadRequestResponseFactory::class)
        ->tag('oauth2_security_message_factory')
    ;

    $container->set('oauth2_security.message_factory.405')
        ->class(MethodNotAllowedResponseFactory::class)
        ->tag('oauth2_security_message_factory')
    ;

    $container->set('oauth2_security.message_factory.501')
        ->class(NotImplementedResponseFactory::class)
        ->tag('oauth2_security_message_factory')
    ;

    $container->set('oauth2_security.message_factory.303')
        ->class(RedirectResponseFactory::class)
        ->tag('oauth2_security_message_factory')
    ;

    $container->set(AccessTokenResolver::class);

    $container->set(RequestListener::class)
        ->tag('kernel.event_listener', ['event' => 'security.authentication.success', 'method' => 'onKernelRequest'])
    ;

    if (interface_exists(ExpressionFunctionProviderInterface::class) && class_exists(ExpressionFunction::class)) {
        $container->set(ExpressionLanguageProvider::class)
            ->tag('security.expression_language_provider')
        ;
    }
};
