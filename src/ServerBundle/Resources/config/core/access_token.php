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
use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandlerManager;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenIntrospectionTypeHint;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRevocationTypeHint;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->public()
        ->autoconfigure();

    $container->set(AccessTokenHandlerManager::class);

    $container->set(AccessTokenIntrospectionTypeHint::class)
        ->args([
            ref(\OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository::class),
        ]);

    $container->set(AccessTokenRevocationTypeHint::class)
        ->args([
            ref(\OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository::class),
        ]);

    $container->set(\OAuth2Framework\ServerBundle\Service\RandomAccessTokenIdGenerator::class);
};
