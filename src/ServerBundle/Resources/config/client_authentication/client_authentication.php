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
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientAuthenticationMiddleware;
use OAuth2Framework\Component\ClientAuthentication\Rule\ClientAuthenticationMethodRule;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private();

    $container->set('oauth2_server.client_authentication.method_manager')
        ->class(AuthenticationMethodManager::class);

    $container->set('oauth2_server.client_authentication.middleware')
        ->class(ClientAuthenticationMiddleware::class)
        ->args([
            ref('oauth2_server.client.repository'),
            ref('oauth2_server.client_authentication.method_manager'),
        ]);

    $container->set('oauth2_server.client_authentication.method_rule')
        ->autoconfigure()
        ->class(ClientAuthenticationMethodRule::class)
        ->args([
            ref('oauth2_server.client_authentication.method_manager'),
        ]);
};
