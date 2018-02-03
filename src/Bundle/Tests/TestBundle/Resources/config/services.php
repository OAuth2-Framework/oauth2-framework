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
use OAuth2Framework\Bundle\Tests\TestBundle\Listener;
use OAuth2Framework\Bundle\Tests\TestBundle\Service\AccessTokenHandler;
use OAuth2Framework\Bundle\Tests\TestBundle\Service\UserProvider;
use OAuth2Framework\Bundle\Tests\TestBundle\Service\EventStore;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set('MyClientRepository')
        ->class(\OAuth2Framework\Bundle\Tests\TestBundle\Entity\ClientRepository::class)
        ->args([
            ref('EventStore.Client'),
            ref('event_recorder'),
            ref('cache.app'),
        ]);

    $container->set('MyRefreshTokenRepository')
        ->class(\OAuth2Framework\Bundle\Tests\TestBundle\Entity\RefreshTokenRepository::class)
        ->args([
            ref('EventStore.RefreshToken'),
            ref('event_recorder'),
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
            ref('EventStore.AccessToken'),
            ref('event_bus'),
            ref('cache.app'),
        ]);

    $container->set('MyAuthorizationCodeRepository')
        ->class(\OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository::class)
        ->args([
            ref('EventStore.AuthCode'),
            ref('event_bus'),
            ref('cache.app'),
        ]);

//    $container->set('MyResourceServerRepository')
//        ->class(\OAuth2Framework\Bundle\Tests\TestBundle\Entity\ResourceServerRepository::class);

    $container->set('MyScopeRepository')
        ->class(\OAuth2Framework\Bundle\Tests\TestBundle\Entity\ScopeRepository::class);

    $container->set(UriFactory::class);

    $container->set('EventStore.RefreshToken')
        ->class(EventStore::class)
        ->args([
            '%kernel.cache_dir%',
            'refresh_token',
        ]);

    $container->set('EventStore.AccessToken')
        ->class(EventStore::class)
        ->args([
            '%kernel.cache_dir%',
            'access_token',
        ]);

    $container->set('EventStore.Client')
        ->class(EventStore::class)
        ->args([
            '%kernel.cache_dir%',
            'client',
        ]);

    $container->set('EventStore.InitialAccessToken')
        ->class(EventStore::class)
        ->args([
            '%kernel.cache_dir%',
            'initial_access_token',
        ]);

    $container->set('EventStore.PreConfiguredAuthorization')
        ->class(EventStore::class)
        ->args([
            '%kernel.cache_dir%',
            'pre_configured_authorization',
        ]);

    $container->set('EventStore.AuthCode')
        ->class(EventStore::class)
        ->args([
            '%kernel.cache_dir%',
            'auth_code',
        ]);

    $container->set(Listener\ClientCreatedListener::class)
        ->tag('event_subscriber', ['subscribes_to' => \OAuth2Framework\Component\Core\Client\Event\ClientCreatedEvent::class]);
    $container->set(Listener\ClientDeletedListener::class)
        ->tag('event_subscriber', ['subscribes_to' => \OAuth2Framework\Component\Core\Client\Event\ClientDeletedEvent::class]);
    $container->set(Listener\ClientOwnerChangedListener::class)
        ->tag('event_subscriber', ['subscribes_to' => \OAuth2Framework\Component\Core\Client\Event\ClientOwnerChangedEvent::class]);
    $container->set(Listener\ClientParametersUpdatedListener::class)
        ->tag('event_subscriber', ['subscribes_to' => \OAuth2Framework\Component\Core\Client\Event\ClientParametersUpdatedEvent::class]);

    $container->set(Listener\AccessTokenCreatedListener::class)
        ->tag('event_subscriber', ['subscribes_to' => \OAuth2Framework\Component\Core\AccessToken\Event\AccessTokenCreatedEvent::class]);
    $container->set(Listener\AccessTokenRevokedListener::class)
        ->tag('event_subscriber', ['subscribes_to' => \OAuth2Framework\Component\Core\AccessToken\Event\AccessTokenRevokedEvent::class]);

    $container->set(Listener\RefreshTokenCreatedListener::class)
        ->tag('event_subscriber', ['subscribes_to' => \OAuth2Framework\Component\RefreshTokenGrant\Event\RefreshTokenCreatedEvent::class]);
    $container->set(Listener\RefreshTokenRevokedListener::class)
        ->tag('event_subscriber', ['subscribes_to' => \OAuth2Framework\Component\RefreshTokenGrant\Event\RefreshTokenRevokedEvent::class]);

    $container->set(Listener\AuthCodeCreatedListener::class)
        ->tag('event_subscriber', ['subscribes_to' => \OAuth2Framework\Component\AuthorizationCodeGrant\Event\AuthorizationCodeCreatedEvent::class]);

    $container->set(Listener\AuthCodeRevokedListener::class)
        ->tag('event_subscriber', ['subscribes_to' => \OAuth2Framework\Component\AuthorizationCodeGrant\Event\AuthorizationCodeRevokedEvent::class]);
    $container->set(Listener\AuthCodeMarkedAsUsedListener::class)
        ->tag('event_subscriber', ['subscribes_to' => \OAuth2Framework\Component\AuthorizationCodeGrant\Event\AuthorizationCodeMarkedAsUsedEvent::class]);

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
