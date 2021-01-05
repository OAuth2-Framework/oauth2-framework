<?php

declare(strict_types=1);
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\ClientRule\ApplicationTypeParametersRule;
use OAuth2Framework\Component\ClientRule\ClientIdIssuedAtRule;
use OAuth2Framework\Component\ClientRule\CommonParametersRule;
use OAuth2Framework\Component\ClientRule\ContactsParametersRule;
use OAuth2Framework\Component\ClientRule\RedirectionUriRule;
use OAuth2Framework\Component\ClientRule\JwksRule;

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

    $container->set(RuleManager::class);

    $container->set(ApplicationTypeParametersRule::class);
    $container->set(ClientIdIssuedAtRule::class);
    $container->set(CommonParametersRule::class);
    $container->set(ContactsParametersRule::class);
    $container->set(RedirectionUriRule::class);
    $container->set(JwksRule::class)
        ->args([
            ref(JKUFactory::class)->nullOnInvalid(),
        ])
    ;
};
