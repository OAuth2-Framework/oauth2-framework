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
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;
use OAuth2Framework\Component\OpenIdConnect\Rule;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\UserInfoScopeSupportManager;
use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use OAuth2Framework\Component\OpenIdConnect\NonceParameterChecker;
use OAuth2Framework\Component\OpenIdConnect\OpenIdConnectExtension;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(UserInfoScopeSupportManager::class);

    $container->set(Claim\ClaimManager::class);
    $container->set(Claim\Address::class);
    $container->set(Claim\AuthenticationTime::class);
    $container->set(Claim\Birthdate::class);
    $container->set(Claim\Email::class);
    $container->set(Claim\EmailVerified::class);
    $container->set(Claim\FamilyName::class);
    $container->set(Claim\Gender::class);
    $container->set(Claim\GivenName::class);
    $container->set(Claim\Locale::class);
    $container->set(Claim\MiddleName::class);
    $container->set(Claim\Name::class);
    $container->set(Claim\Nickname::class);
    $container->set(Claim\PhoneNumber::class);
    $container->set(Claim\PhoneNumberVerified::class);
    $container->set(Claim\Picture::class);
    $container->set(Claim\PreferredUsername::class);
    $container->set(Claim\Profile::class);
    $container->set(Claim\UpdatedAt::class);
    $container->set(Claim\Website::class);
    $container->set(Claim\Zoneinfo::class);

    $container->set(Claim\ClaimSourceManager::class);

    $container->set(UserInfo::class)
        ->args([
            ref(UserInfoScopeSupportManager::class),
            ref(Claim\ClaimSourceManager::class),
        ]);

    $container->set(OpenIdConnectExtension::class)
        ->args([
            ref(IdTokenBuilderFactory::class),
            '%oauth2_server.openid_connect.id_token.default_signature_algorithm%',
            ref('jose.jws_builder.oauth2_server.openid_connect.id_token'),
            ref('jose.key_set.oauth2_server.openid_connect.id_token'),
            ref('jose.encrypter.oauth2_server.openid_connect.id_token')->nullOnInvalid(),
        ]);

    $container->set(IdTokenBuilderFactory::class)
        ->args([
            '%oauth2_server.server_uri%',
            ref(UserInfo::class),
            '%oauth2_server.openid_connect.id_token.lifetime%',
        ]);

    $container->set(Rule\IdTokenAlgorithmsRule::class)
        ->args([
            ref('jose.jws_builder.oauth2_server.openid_connect.id_token'),
            ref('jose.jwe_builder.oauth2_server.openid_connect.id_token')->nullOnInvalid(),
        ]);

    $container->set(Rule\SubjectTypeRule::class)
        ->args([
            ref(UserInfo::class),
        ]);

    $container->set(NonceParameterChecker::class);
};
