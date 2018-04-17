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
use OAuth2Framework\SecurityBundle\Security\Authentication\Provider\OAuth2Provider;
use OAuth2Framework\SecurityBundle\Security\Firewall\OAuth2Listener;
use OAuth2Framework\SecurityBundle\Security\EntryPoint\OAuth2EntryPoint;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $container->set('oauth2.security.token_type_manager.default')
        ->class(TokenTypeManager::class);
    $container->set(AccessTokenHandlerManager::class);
    $container->set(OAuth2Provider::class);
    $container->set(OAuth2Listener::class)
        ->args([
            ref(TokenStorageInterface::class),
            ref(AuthenticationManagerInterface::class),
            ref('oauth2.security.token_type_manager'),
            ref(AccessTokenHandlerManager::class),
        ]);
    $container->set(OAuth2EntryPoint::class);
};
