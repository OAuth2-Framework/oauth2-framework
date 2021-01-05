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

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Pairwise\EncryptedSubjectIdentifier;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Repository\AccessTokenRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Repository\AuthorizationCodeRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Repository\ClientRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Repository\ConsentRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Repository\InitialAccessTokenRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Repository\RefreshTokenRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Repository\ResourceOwnerPasswordCredentialRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Repository\ResourceServerRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Repository\ScopeRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Repository\TrustedIssuerRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Repository\UserAccountRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Service\ConsentHandler;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Service\LoginHandler;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Service\SymfonyUserAccountDiscovery;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Service\UserProvider;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->public()
        ->autowire()
        ->autoconfigure()
    ;

    $container->set(LoginHandler::class);
    $container->set(ConsentHandler::class);
    $container->set(ClientRepository::class);
    $container->set(RefreshTokenRepository::class);
    $container->set(ResourceOwnerPasswordCredentialRepository::class);
    $container->set(UserAccountRepository::class);
    $container->set(ResourceServerRepository::class);
    $container->set(TrustedIssuerRepository::class);
    $container->set(ConsentRepository::class);
    $container->set(UserProvider::class);
    $container->set(SymfonyUserAccountDiscovery::class);
    $container->set(AccessTokenRepository::class);
    $container->set(AuthorizationCodeRepository::class);
    $container->set(ScopeRepository::class);
    $container->set(InitialAccessTokenRepository::class);
    $container->set(Psr17Factory::class);
    $container->alias(ResponseFactoryInterface::class, Psr17Factory::class);
    $container->alias(RequestFactoryInterface::class, Psr17Factory::class);

    $container->set('MyPairwiseSubjectIdentifier')
        ->class(EncryptedSubjectIdentifier::class)
        ->args([
            'This is my secret Key !!!',
            'aes-128-cbc',
        ])
    ;

    /*$container->set(ResourceServerAuthMethodByIpAddress::class)
        ->tag('token_introspection_endpoint_auth_method');*/
};
