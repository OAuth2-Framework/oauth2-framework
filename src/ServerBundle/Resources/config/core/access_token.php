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
use OAuth2Framework\Component\Core\AccessToken\AccessTokenIntrospectionTypeHint;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRevocationTypeHint;
use OAuth2Framework\ServerBundle\Service\RandomAccessTokenIdGenerator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private();

    $container->set('oauth2_security.access_token.introspection_type_hint')
        ->class(AccessTokenIntrospectionTypeHint::class)
        ->args([
            ref('oauth2_security.access_token.repository'),
        ]);

    $container->set('oauth2_security.access_token.introspection_type_hint')
        ->class(AccessTokenRevocationTypeHint::class)
        ->args([
            ref('oauth2_security.access_token.repository'),
        ]);

    $container->set(RandomAccessTokenIdGenerator::class);
};
