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
use OAuth2Framework\Component\ClientRule;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private();

    $container->set('oauth2_server.client_rule.manager')
        ->class(ClientRule\RuleManager::class);

    $container->set(ClientRule\ApplicationTypeParametersRule::class)
        ->autoconfigure();

    $container->set(ClientRule\ClientIdIssuedAtRule::class)
        ->autoconfigure();

    $container->set(ClientRule\CommonParametersRule::class)
        ->autoconfigure();

    $container->set(ClientRule\ContactsParametersRule::class)
        ->autoconfigure();

    $container->set(ClientRule\RedirectionUriRule::class)
        ->autoconfigure();
};
