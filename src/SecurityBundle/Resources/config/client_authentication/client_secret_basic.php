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
use OAuth2Framework\Component\ClientAuthentication\ClientSecretBasic;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private();

    $container->set(ClientSecretBasic::class)
        ->args([
            '%oauth2_server.client_authentication.client_secret_basic.realm%',
            '%oauth2_server.client_authentication.client_secret_basic.secret_lifetime%',
        ])
        ->tag('oauth2_server_client_authentication');
};
