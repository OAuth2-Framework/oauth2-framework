<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Jose\Component\KeyManagement\JKUFactory;
use OAuth2Framework\Component\ClientRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(ClientRule\RuleManager::class);

    $container->set(ClientRule\ApplicationTypeParametersRule::class);
    $container->set(ClientRule\ClientIdIssuedAtRule::class);
    $container->set(ClientRule\CommonParametersRule::class);
    $container->set(ClientRule\ContactsParametersRule::class);
    $container->set(ClientRule\RedirectionUriRule::class);
    $container->set(ClientRule\JwksRule::class)
        ->args([
            ref(JKUFactory::class)->nullOnInvalid(),
        ])
    ;
};
