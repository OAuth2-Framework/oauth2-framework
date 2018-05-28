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
use OAuth2Framework\Component\OpenIdConnect\IdTokenGrant\CodeIdTokenTokenResponseType;
use OAuth2Framework\Component\OpenIdConnect\IdTokenGrant\IdTokenResponseType;
use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(CodeIdTokenTokenResponseType::class)
        ->args([
            ref(AuthorizationCodeResponseType::class),
            ref(IdTokenResponseType::class),
            ref(TokenResponseType::class),
        ]);
};
