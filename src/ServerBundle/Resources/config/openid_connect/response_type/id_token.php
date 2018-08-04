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

use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use OAuth2Framework\Component\OpenIdConnect\IdTokenGrant\IdTokenResponseType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(IdTokenResponseType::class)
        ->args([
            ref(IdTokenBuilderFactory::class),
            '%oauth2_server.openid_connect.id_token.default_signature_algorithm%',
            ref('jose.jws_builder.oauth2_server.openid_connect.id_token'),
            ref('jose.key_set.oauth2_server.openid_connect.id_token'),
            ref('jose.encrypter.oauth2_server.openid_connect.id_token')->nullOnInvalid(),
        ]);
};
