<?php

declare(strict_types=1);
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\ClaimManager;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Address;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\AuthenticationTime;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Birthdate;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Email;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\EmailVerified;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\FamilyName;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Gender;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\GivenName;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Locale;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\MiddleName;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Name;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Nickname;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\PhoneNumber;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\PhoneNumberVerified;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Picture;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\PreferredUsername;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Profile;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\UpdatedAt;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Website;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\Zoneinfo;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\ClaimSourceManager;
use OAuth2Framework\Component\OpenIdConnect\Rule\IdTokenAlgorithmsRule;
use OAuth2Framework\Component\OpenIdConnect\Rule\SubjectTypeRule;
use OAuth2Framework\Component\OpenIdConnect\ParameterChecker\NonceParameterChecker;
use OAuth2Framework\Component\OpenIdConnect\ParameterChecker\ClaimsParameterChecker;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use OAuth2Framework\Component\OpenIdConnect\OpenIdConnectExtension;
use OAuth2Framework\Component\OpenIdConnect\ParameterChecker;
use OAuth2Framework\Component\OpenIdConnect\Rule;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\UserInfoScopeSupportManager;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(UserInfoScopeSupportManager::class);

    $container->set(ClaimManager::class);
    $container->set(Address::class);
    $container->set(AuthenticationTime::class);
    $container->set(Birthdate::class);
    $container->set(Email::class);
    $container->set(EmailVerified::class);
    $container->set(FamilyName::class);
    $container->set(Gender::class);
    $container->set(GivenName::class);
    $container->set(Locale::class);
    $container->set(MiddleName::class);
    $container->set(Name::class);
    $container->set(Nickname::class);
    $container->set(PhoneNumber::class);
    $container->set(PhoneNumberVerified::class);
    $container->set(Picture::class);
    $container->set(PreferredUsername::class);
    $container->set(Profile::class);
    $container->set(UpdatedAt::class);
    $container->set(Website::class);
    $container->set(Zoneinfo::class);

    $container->set(ClaimSourceManager::class);

    $container->set(UserInfo::class)
        ->args([
            ref(UserInfoScopeSupportManager::class),
            ref(ClaimManager::class),
            ref(ClaimSourceManager::class),
        ])
    ;

    $container->set(OpenIdConnectExtension::class)
        ->args([
            ref(IdTokenBuilderFactory::class),
            '%oauth2_server.openid_connect.id_token.default_signature_algorithm%',
            ref('jose.jws_builder.oauth2_server.openid_connect.id_token'),
            ref('jose.key_set.oauth2_server.openid_connect.id_token'),
        ])
    ;

    $container->set(IdTokenBuilderFactory::class)
        ->args([
            '%oauth2_server.server_uri%',
            ref(UserInfo::class),
            '%oauth2_server.openid_connect.id_token.lifetime%',
        ])
    ;

    $container->set(IdTokenAlgorithmsRule::class)
        ->args([
            ref('jose.jws_builder.oauth2_server.openid_connect.id_token'),
            ref('jose.jwe_builder.oauth2_server.openid_connect.id_token')->nullOnInvalid(),
        ])
    ;

    $container->set(SubjectTypeRule::class)
        ->args([
            ref(UserInfo::class),
        ])
    ;

    $container->set(NonceParameterChecker::class);
    $container->set(ClaimsParameterChecker::class);
};
