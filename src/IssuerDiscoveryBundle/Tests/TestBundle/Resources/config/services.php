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
use OAuth2Framework\IssuerDiscoveryBundle\Tests\TestBundle\Entity\ResourceRepository;
use OAuth2Framework\IssuerDiscoveryBundle\Tests\TestBundle\Service\ResponseFactory;
use OAuth2Framework\IssuerDiscoveryBundle\Tests\TestBundle\Service\UriPathResolver;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->public()
        ->autoconfigure();

    $container->set(ResourceRepository::class);
    $container->set(ResponseFactory::class);
    $container->set(UriPathResolver::class);
};
