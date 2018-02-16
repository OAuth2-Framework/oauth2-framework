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
use OAuth2Framework\Component\ClientAuthentication\Rule\ClientAuthenticationMethodEndpointRule;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(AuthenticationMethodManager::class);
    $container->set(ClientAuthenticationMethodEndpointRule::class)
        ->args([
            ref(AuthenticationMethodManager::class),
        ]);

    $container->set(ClientAuthenticationMiddleware::class)
        ->args([
            ref('oauth2_server.client_repository'),
            ref(AuthenticationMethodManager::class),
        ]);
};
