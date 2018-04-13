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
use OAuth2Framework\IssuerDiscoveryBundle\Service\IssuerDiscoveryFactory;
use OAuth2Framework\IssuerDiscoveryBundle\Service\RouteLoader;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(RouteLoader::class)
        ->tag('routing.loader');

    $container->set(IssuerDiscoveryFactory::class)
        ->args([
            ref('issuer_discovery.response_factory'),
            ref(IdentifierResolver\IdentifierResolverManager::class),
        ]);

    $container->set(IdentifierResolver\IdentifierResolverManager::class);
    $container->set(IdentifierResolver\UriResolver::class);
    $container->set(IdentifierResolver\AccountResolver::class);
};
