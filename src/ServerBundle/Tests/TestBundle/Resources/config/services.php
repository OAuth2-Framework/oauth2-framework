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
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Pairwise\EncryptedSubjectIdentifier;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AccessTokenIdGenerator;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AccessTokenRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AuthorizationCodeIdGenerator;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AuthorizationCodeRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\ClientRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\InitialAccessTokenRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\RefreshTokenRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\ResourceOwnerPasswordCredentialManager;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\ResourceServerRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\ScopeRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\TrustedIssuerRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\UserAccountRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\UserRepository;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Handler\ConsentHandler;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Handler\LoginHandler;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Handler\SelectAccountHandler;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Service\SymfonyUserDiscovery;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Service\UserProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $container->set(ClientRepository::class);

    $container->set(RefreshTokenRepository::class);

    $container->set(ResourceOwnerPasswordCredentialManager::class);

    $container->set(UserRepository::class);
    $container->set(UserAccountRepository::class);

    $container->set(ResourceServerRepository::class);

    $container->set(TrustedIssuerRepository::class);

    $container->set(UserProvider::class);

    $container->set(SymfonyUserDiscovery::class);

    $container->set(AccessTokenIdGenerator::class);

    $container->set(AccessTokenRepository::class);

    $container->set(AuthorizationCodeIdGenerator::class);

    $container->set(AuthorizationCodeRepository::class);

    $container->set(ScopeRepository::class);

    $container->set(InitialAccessTokenRepository::class);

    $container->set(UriFactory::class);

    $container->set('MyPairwiseSubjectIdentifier')
        ->class(EncryptedSubjectIdentifier::class)
        ->args([
            'This is my secret Key !!!',
            'aes-128-cbc',
        ]);

    /*$container->set(ResourceServerAuthMethodByIpAddress::class)
        ->tag('token_introspection_endpoint_auth_method');*/

    $container->set(ConsentHandler::class);
    $container->set(LoginHandler::class);
    $container->set(SelectAccountHandler::class);
};
