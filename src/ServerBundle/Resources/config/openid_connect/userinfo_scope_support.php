<?php

declare(strict_types=1);
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\AddressScopeSupport;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\EmailScopeSupport;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\PhoneScopeSupport;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\ProfileScopeSupport;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(AddressScopeSupport::class);
    $container->set(EmailScopeSupport::class);
    $container->set(PhoneScopeSupport::class);
    $container->set(ProfileScopeSupport::class);
};
