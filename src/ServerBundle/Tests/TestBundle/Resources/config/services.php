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

use Http\Factory\Diactoros\UriFactory;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\ClientRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\UserManager;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\UserRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\RefreshTokenRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\ResourceServerRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AccessTokenIdGenerator;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AccessTokenRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AuthorizationCodeRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\ScopeRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\InitialAccessTokenRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\TrustedIssuerRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Service\AccessTokenHandler;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Service\UserProvider;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Pairwise\EncryptedSubjectIdentifier;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->public()
        ->autoconfigure();

    $container->set('MyClientRepository')
        ->class(ClientRepository::class);

    $container->set('MyRefreshTokenRepository')
        ->class(RefreshTokenRepository::class);

    $container->set('MyUserAccountManager')
        ->class(UserManager::class);

    $container->set('MyUserAccountRepository')
        ->class(UserRepository::class);

    $container->set('MyResourceServerRepository')
        ->class(ResourceServerRepository::class);

    $container->set('MyTrustedIssuerRepository')
        ->class(TrustedIssuerRepository::class);

    $container->set(UserProvider::class)
        ->args([
            ref('MyUserAccountRepository'),
        ]);

    $container->set('MyAccessTokenIdGenerator')
        ->class(AccessTokenIdGenerator::class);

    $container->set('MyAccessTokenRepository')
        ->class(AccessTokenRepository::class);

    $container->set('MyAuthorizationCodeRepository')
        ->class(AuthorizationCodeRepository::class);

    $container->set('MyScopeRepository')
        ->class(ScopeRepository::class);

    $container->set('MyInitialAccessTokenRepository')
        ->class(InitialAccessTokenRepository::class);

    $container->set(UriFactory::class);

    $container->set(AccessTokenHandler::class)
        ->tag('oauth2_server_access_token_handler');

    $container->set('MyPairwiseSubjectIdentifier')
        ->class(EncryptedSubjectIdentifier::class)
        ->args([
            'This is my secret Key !!!',
            'aes-128-cbc',
            mb_substr('This is my salt or my IV !!!', 0, 16, '8bit'),
            mb_substr('This is my salt or my IV !!!', 0, 16, '8bit'),
        ]);

    /*$container->set(ResourceServerAuthMethodByIpAddress::class)
        ->tag('token_introspection_endpoint_auth_method');*/
};
