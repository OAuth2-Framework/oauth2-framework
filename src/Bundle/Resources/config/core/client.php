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

use OAuth2Framework\Component\Core\Client\Command;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->public()
        ->autoconfigure()
        ->autowire();

    $container->set(Command\ChangeOwnerCommandHandler::class)
        ->args([
            ref('oauth2_server.client.repository'),
        ]);

    $container->set(Command\CreateClientCommandHandler::class)
        ->args([
            ref('oauth2_server.client.repository'),
        ]);

    $container->set(Command\DeleteClientCommandHandler::class)
        ->args([
            ref('oauth2_server.client.repository'),
        ]);

    $container->set(Command\UpdateClientCommandHandler::class)
        ->args([
            ref('oauth2_server.client.repository'),
        ]);
};