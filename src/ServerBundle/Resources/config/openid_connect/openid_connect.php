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
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ClaimSource\ClaimSourceManager;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;
use OAuth2Framework\Component\OpenIdConnect\Rule;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\UserInfoScopeSupportManager;
use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(UserInfoScopeSupportManager::class);

    $container->set(ClaimSourceManager::class);

    $container->set(UserInfo::class)
        ->args([
            ref(UserInfoScopeSupportManager::class),
            ref(ClaimSourceManager::class),
        ]);

    /*$container->set(Extension::class)
        ->args([
            ref(IdTokenBuilderFactory::class),
            '%oauth2_server.openid_connect.id_token.default_signature_algorithm%',
            ref('jose.jws_builder.id_token'),
            ref('jose.key_set.oauth2_server.key_set.signature'),
            ref('jose.jwe_builder.id_token')->nullOnInvalid(),
        ])
        ->tag('oauth2_server_token_endpoint_extension');*/

    $container->set(IdTokenBuilderFactory::class)
        ->args([
            '%oauth2_server.server_uri%',
            ref(UserInfo::class),
            '%oauth2_server.openid_connect.id_token.lifetime%',
        ]);

    $container->set(Rule\IdTokenAlgorithmsRule::class)
        ->args([
            ref('jose.jws_builder.id_token'),
            ref('jose.jwe_builder.id_token')->nullOnInvalid(),
        ]);

    $container->set(Rule\SubjectTypeRule::class)
        ->args([
            ref(UserInfo::class),
        ]);
};
