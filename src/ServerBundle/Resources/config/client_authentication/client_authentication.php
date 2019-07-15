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

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientAuthenticationMiddleware;
use OAuth2Framework\Component\ClientAuthentication\Rule\ClientAuthenticationMethodRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
    ;

    $container->set(AuthenticationMethodManager::class);

    $container->set('oauth2_server.client_authentication.middleware')
        ->class(ClientAuthenticationMiddleware::class)
        ->args([
            ref(\OAuth2Framework\Component\Core\Client\ClientRepository::class),
            ref(AuthenticationMethodManager::class),
        ])
    ;

    $container->set('oauth2_server.client_authentication.method_rule')
        ->autoconfigure()
        ->class(ClientAuthenticationMethodRule::class)
        ->args([
            ref(AuthenticationMethodManager::class),
        ])
    ;
};
