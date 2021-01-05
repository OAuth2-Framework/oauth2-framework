<?php

declare(strict_types=1);
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\Plain;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\S256;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeGrantType;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(AuthorizationCodeGrantType::class)
        ->args([
            ref(AuthorizationCodeRepository::class),
            ref(PKCEMethodManager::class),
        ])
    ;

    $container->set(AuthorizationCodeResponseType::class)
        ->args([
            ref(AuthorizationCodeRepository::class),
            '%oauth2_server.grant.authorization_code.lifetime%',
            ref(PKCEMethodManager::class),
            '%oauth2_server.grant.authorization_code.enforce_pkce%',
        ])
    ;

    $container->set(PKCEMethodManager::class);
    $container->set(Plain::class)
        ->tag('oauth2_server_pkce_method', ['alias' => 'plain'])
    ;
    $container->set(S256::class)
        ->tag('oauth2_server_pkce_method', ['alias' => 'S256'])
    ;
};
