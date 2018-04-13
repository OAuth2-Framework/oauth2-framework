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
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(ScopeSupport\AddressScopeSupport::class)
        ->tag('oauth2_server_userinfo_scope_support');

    $container->set(ScopeSupport\EmailScopeSupport::class);
    $container->set(ScopeSupport\PhoneScopeSupport::class);
    $container->set(ScopeSupport\ProfilScopeSupport::class);
};
