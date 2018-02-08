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
use OAuth2Framework\Bundle\Tests\TestBundle\Entity\ResourceRepository;
use OAuth2Framework\Bundle\Tests\TestBundle\Entity\UserManager;
use OAuth2Framework\Bundle\Tests\TestBundle\Entity\UserRepository;
use OAuth2Framework\Bundle\Tests\TestBundle\Service\AccessTokenHandler;
use OAuth2Framework\Bundle\Tests\TestBundle\Service\UserProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('MyClientRepository')
        ->class(\OAuth2Framework\Bundle\Tests\TestBundle\Entity\ClientRepository::class)
        ->args([
            ref('cache.app'),
        ]);

    $container->set('MyRefreshTokenRepository')
        ->class(\OAuth2Framework\Bundle\Tests\TestBundle\Entity\RefreshTokenRepository::class)
        ->args([
            ref('cache.app'),
        ]);

    $container->set('MyUserAccountManager')
        ->class(UserManager::class);

    $container->set('MyUserAccountRepository')
        ->class(UserRepository::class);

    $container->set('MyResourceServerRepository')
        ->class(\OAuth2Framework\Component\Core\ResourceServer\ResourceServerRepository::class);

    $container->set(UserProvider::class)
        ->args([
            ref('MyUserAccountRepository'),
        ]);

    $container->set('MyAccessTokenRepository')
        ->class(\OAuth2Framework\Bundle\Tests\TestBundle\Entity\AccessTokenByReferenceRepository::class)
        ->args([
            100,
            150,
            1800,
            ref('cache.app'),
        ]);

    $container->set('MyAuthorizationCodeRepository')
        ->class(\OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository::class)
        ->args([
            ref('cache.app'),
        ]);

    $container->set('MyScopeRepository')
        ->class(\OAuth2Framework\Bundle\Tests\TestBundle\Entity\ScopeRepository::class);

    $container->set('MyInitialAccessTokenRepository')
        ->class(\OAuth2Framework\Bundle\Tests\TestBundle\Entity\InitialAccessTokenRepository::class)
        ->args([
            ref('cache.app'),
        ]);

    $container->set(UriFactory::class);

    $container->set(AccessTokenHandler::class)
        ->tag('oauth2_server_access_token_handler');

    $container->set('MyPairwiseSubjectIdentifier')
        ->class(\OAuth2Framework\Component\OpenIdConnect\UserInfo\Pairwise\EncryptedSubjectIdentifier::class)
        ->args([
            'This is my secret Key !!!',
            'aes-128-cbc',
            mb_substr('This is my salt or my IV !!!', 0, 16, '8bit'),
            mb_substr('This is my salt or my IV !!!', 0, 16, '8bit'),
        ]);

    $container->set('MyResourceRepository')
        ->class(ResourceRepository::class);

    /*$container->set(ResourceServerAuthMethodByIpAddress::class)
        ->tag('token_introspection_endpoint_auth_method');*/
};
