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

use OAuth2Framework\SecurityBundle\Service\MacToken;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
        ->autowire();

    $container->set(MacToken::class)
        ->args([
            '%oauth2_security.token_type.mac_token.algorithm%',
            '%oauth2_security.token_type.mac_token.timestamp_lifetime%',
            '%oauth2_security.token_type.mac_token.min_length%',
            '%oauth2_security.token_type.mac_token.max_length%',
        ])
        ->tag('oauth2_security_token_type');
};
