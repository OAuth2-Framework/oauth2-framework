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

use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use OAuth2Framework\Component\ImplicitGrant\ImplicitGrantType;
use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(ImplicitGrantType::class);

    $container->set(TokenResponseType::class)
        ->args([
            ref(AccessTokenRepository::class),
            '%oauth2_server.access_token_lifetime%',
            ref(TokenTypeGuesser::class),
        ])
    ;
};
