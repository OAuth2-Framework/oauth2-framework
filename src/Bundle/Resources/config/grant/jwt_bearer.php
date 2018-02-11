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

use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use OAuth2Framework\Component\JwtBearerGrant\TrustedIssuerManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(TrustedIssuerManager::class);

    $container->set(JwtBearerGrantType::class)
        ->args([
            ref(TrustedIssuerManager::class),
            ref('jose.jws_loader.jwt_bearer'),
            ref('jose.claim_checker.jwt_bearer'),
            ref('oauth2_server.'),
            ref('oauth2_server.user_account_repository'),
        ]);
};
