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
use OAuth2Framework\Component\ClientAuthentication\None;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private();

    $container
        ->set(None::class)
        ->tag('oauth2_server_client_authentication');
};
