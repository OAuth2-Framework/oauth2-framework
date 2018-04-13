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

use OAuth2Framework\ServerBundle\TokenType\MacToken;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
        ->autowire();

    $container->set(MacToken::class)
        ->args([
            '%oauth2_server.token_type.mac_token.algorithm%',
            '%oauth2_server.token_type.mac_token.timestamp_lifetime%',
            '%oauth2_server.token_type.mac_token.min_length%',
            '%oauth2_server.token_type.mac_token.max_length%',
        ])
        ->tag('oauth2_server_token_type', ['scheme' => 'MAC']);
};
