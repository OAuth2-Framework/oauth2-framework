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

use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(ScopeSupport\AddressScopeSupport::class);
    $container->set(ScopeSupport\EmailScopeSupport::class);
    $container->set(ScopeSupport\PhoneScopeSupport::class);
    $container->set(ScopeSupport\ProfileScopeSupport::class);
};
