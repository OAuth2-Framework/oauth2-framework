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
use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandlerManager;
use OAuth2Framework\ServerBundle\Security\Authentication\Provider\OAuth2Provider;
use OAuth2Framework\ServerBundle\Security\Firewall\OAuth2Listener;
use OAuth2Framework\ServerBundle\Security\EntryPoint\OAuth2EntryPoint;
use OAuth2Framework\ServerBundle\Annotation;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $container->set(AccessTokenHandlerManager::class);
    $container->set(OAuth2Provider::class);
    $container->set(OAuth2Listener::class)
        ->args([
            ref(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class),
            ref(\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface::class),
            ref(\OAuth2Framework\Component\Core\TokenType\TokenTypeManager::class),
            ref(AccessTokenHandlerManager::class),
            ref('oauth2_message_factory_manager_with_token_authentication'),
        ]);
    $container->set(OAuth2EntryPoint::class)
        ->args([
            ref('oauth2_message_factory_manager_with_token_authentication'),
        ]);

    $container->set(Annotation\AnnotationDriver::class)
        ->args([
            ref(\Doctrine\Common\Annotations\Reader::class),
            ref(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class),
            ref('oauth2_message_factory_manager_with_token_authentication'),
        ])
        ->tag('kernel.event_listener', [
            'event' => 'kernel.controller',
            'method' => 'onKernelController',
        ]);
    $container->set(Annotation\Checker\ClientIdChecker::class);
    $container->set(Annotation\Checker\ResourceOwnerIdChecker::class);
    $container->set(Annotation\Checker\ScopeChecker::class);
    $container->set(Annotation\Checker\TokenTypeChecker::class);
};
