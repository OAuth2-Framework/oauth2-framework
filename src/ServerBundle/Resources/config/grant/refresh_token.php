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

use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenEndpointExtension;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenGrantType;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRevocationTypeHint;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(RefreshTokenGrantType::class)
        ->args([
            ref(RefreshTokenRepository::class),
        ])
    ;

    $container->set(RefreshTokenRevocationTypeHint::class)
        ->args([
            ref(RefreshTokenRepository::class),
        ])
    ;

    $container->set(RefreshTokenEndpointExtension::class)
        ->args([
            '%oauth2_server.grant.refresh_token.lifetime%',
            ref(RefreshTokenRepository::class),
        ])
    ;
};
