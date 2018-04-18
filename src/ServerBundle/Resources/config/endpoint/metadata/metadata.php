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
use OAuth2Framework\ServerBundle\Middleware;
use OAuth2Framework\ServerBundle\Controller\MetadataController;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('metadata_endpoint_pipe')
        ->class(Middleware\Pipe::class)
        ->args([[
            ref(MetadataController::class),
        ]])
        ->tag('controller.service_arguments');

    $container->set(MetadataController::class)
        ->args([
            ref('httplug.message_factory'),
            ref(MetadataBuilder::class),
        ]);

    $container->set(MetadataBuilder::class)
        ->args([
            ref('router'),
        ]);
};
