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

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeGrantType;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(AuthorizationCodeGrantType::class)
        ->args([
            ref(\OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository::class),
            ref(PKCEMethod\PKCEMethodManager::class),
        ]);

    $container->set(AuthorizationCodeResponseType::class)
        ->args([
            ref(\OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeIdGenerator::class),
            ref(\OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository::class),
            '%oauth2_server.grant.authorization_code.lifetime%',
            ref(PKCEMethod\PKCEMethodManager::class),
            '%oauth2_server.grant.authorization_code.enforce_pkce%',
        ]);

    $container->set(\OAuth2Framework\ServerBundle\Service\RandomAuthorizationCodeIdGenerator::class);

    $container->set(PKCEMethod\PKCEMethodManager::class);
    $container->set(PKCEMethod\Plain::class)
        ->tag('oauth2_server_pkce_method', ['alias' => 'plain']);
    $container->set(PKCEMethod\S256::class)
        ->tag('oauth2_server_pkce_method', ['alias' => 'S256']);
};
