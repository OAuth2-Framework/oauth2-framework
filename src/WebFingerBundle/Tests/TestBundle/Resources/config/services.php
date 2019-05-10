<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

use OAuth2Framework\WebFingerBundle\Tests\TestBundle\Entity\ResourceRepository;
use OAuth2Framework\WebFingerBundle\Tests\TestBundle\Service\UriPathResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->public()
        ->autoconfigure();

    $container->set(ResourceRepository::class);
    $container->set(UriPathResolver::class);
};
