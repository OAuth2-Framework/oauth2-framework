<?php

declare(strict_types=1);

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\ClientAuthentication\ClientAuthenticationMiddleware;
use OAuth2Framework\Component\ClientAuthentication\Rule\ClientAuthenticationMethodRule;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
    ;

    $container->set(AuthenticationMethodManager::class);

    $container->set('oauth2_server.client_authentication.middleware')
        ->class(ClientAuthenticationMiddleware::class)
        ->args([service(ClientRepository::class), service(AuthenticationMethodManager::class)])
    ;

    $container->set('oauth2_server.client_authentication.method_rule')
        ->autoconfigure()
        ->class(ClientAuthenticationMethodRule::class)
        ->args([service(AuthenticationMethodManager::class)])
    ;
};
