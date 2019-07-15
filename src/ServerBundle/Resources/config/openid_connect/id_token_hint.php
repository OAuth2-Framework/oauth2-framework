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

use OAuth2Framework\Component\OpenIdConnect\IdTokenLoader;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(IdTokenLoader::class)
        ->args([
            ref('jose.jws_loader.oauth2_server.openid_connect.id_token.signature'),
            ref('jose.key_set.oauth2_server.openid_connect.id_token'),
            '%oauth2_server.openid_connect.id_token.signature_algorithms%',
        ])
    ;
};
