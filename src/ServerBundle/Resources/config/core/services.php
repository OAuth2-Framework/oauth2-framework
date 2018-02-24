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

use OAuth2Framework\ServerBundle\Routing\RouteLoader;
use OAuth2Framework\Component\Core\Domain;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
        ->autowire();

    $container->set(RouteLoader::class)
        ->tag('routing.loader');

    $container->set(Domain\DomainConverter::class);
    $container->set(Domain\DomainUriLoader::class);
};
