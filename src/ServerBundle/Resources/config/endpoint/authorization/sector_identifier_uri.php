<?php

declare(strict_types=1);
use OAuth2Framework\Component\AuthorizationEndpoint\Rule\SectorIdentifierUriRule;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\AuthorizationEndpoint;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(SectorIdentifierUriRule::class)
        ->args([
            ref(RequestFactoryInterface::class),
            ref('oauth2_server.http_client'),
        ])
    ;
};
