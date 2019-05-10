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

use OAuth2Framework\Component\ClientAuthentication\ClientSecretPost;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(ClientSecretPost::class)
        ->args([
            '%oauth2_server.client_authentication.client_secret_post.secret_lifetime%',
        ]);
};
