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
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IdentifierResolver;
use OAuth2Framework\Bundle\Service\IssuerDiscoveryFactory;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(IssuerDiscoveryFactory::class)
        ->args([
            ref('httplug.message_factory'),
            ref(IdentifierResolver\IdentifierResolverManager::class),
        ]);

    $container->set(IdentifierResolver\IdentifierResolverManager::class);
    $container->set(IdentifierResolver\UriResolver::class);
    $container->set(IdentifierResolver\AccountResolver::class);
};
