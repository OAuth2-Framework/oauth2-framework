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

use OAuth2Framework\Component\Core\AccessToken\AccessTokenIntrospectionTypeHint;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRevocationTypeHint;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(AccessTokenRevocationTypeHint::class)
        ->args([
            ref(AccessTokenRepository::class),
        ])
    ;

    $container->set(AccessTokenIntrospectionTypeHint::class)
        ->args([
            ref(AccessTokenRepository::class),
        ])
    ;
};
