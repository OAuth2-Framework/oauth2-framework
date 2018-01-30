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
use OAuth2Framework\Bundle\Model\AccessTokenByReferenceRepository;
use OAuth2Framework\Component\Model\Scope\ScopeRepository;
use OAuth2Framework\Bundle\Tests\TestBundle\Entity\ResourceRepository;
use OAuth2Framework\Bundle\Tests\TestBundle\Entity\UserManager;
use OAuth2Framework\Bundle\Tests\TestBundle\Entity\UserRepository;
use OAuth2Framework\Bundle\Tests\TestBundle\Listener;
use OAuth2Framework\Bundle\Tests\TestBundle\Service\AccessTokenHandler;
use OAuth2Framework\Bundle\Tests\TestBundle\Service\UserProvider;
use OAuth2Framework\Bundle\Tests\TestBundle\Service\EventStore;
use OAuth2Framework\Component\Endpoint\UserInfo\Pairwise\EncryptedSubjectIdentifier;
use OAuth2Framework\Component\Event\AccessToken;
use OAuth2Framework\Component\Event\AuthCode;
use OAuth2Framework\Component\Event\Client;
use OAuth2Framework\Component\Event\RefreshToken;
use OAuth2Framework\Component\Tests\Stub\ResourceServerAuthMethodByIpAddress;
use OAuth2Framework\Component\Tests\Stub\ResourceServerRepository;
use function Fluent\autowire;
use function Fluent\create;
use function Fluent\get;

return [
    UriFactory::class => autowire(),

    'MyScopeRepository' => create(ScopeRepository::class)
        ->arguments(
            ['openid', 'email', 'profile', 'address', 'phone', 'offline_access']
        ),

    'EventStore.RefreshToken' => create(EventStore::class)
        ->arguments(
            '%kernel.cache_dir%',
            'refresh_token'
        ),

    'EventStore.AccessToken' => create(EventStore::class)
        ->arguments(
            '%kernel.cache_dir%',
            'access_token'
        ),

    'EventStore.Client' => create(EventStore::class)
        ->arguments(
            '%kernel.cache_dir%',
            'client'
        ),

    'EventStore.InitialAccessToken' => create(EventStore::class)
        ->arguments(
            '%kernel.cache_dir%',
            'initial_access_token'
        ),

    'EventStore.PreConfiguredAuthorization' => create(EventStore::class)
        ->arguments(
            '%kernel.cache_dir%',
            'pre_configured_authorization'
        ),

    'EventStore.AuthCode' => create(EventStore::class)
        ->arguments(
            '%kernel.cache_dir%',
            'auth_code'
        ),

    'MyAccessTokenRepository' => create(AccessTokenByReferenceRepository::class)
        ->arguments(
            100,
            150,
            1800,
            get('EventStore.AccessToken'),
            get('event_bus'),
            get('cache.app')
        ),

    'MyUserAccountManager' => create(UserManager::class),
    'MyUserAccountRepository' => create(UserRepository::class),
    'MyResourceServerRepository' => create(ResourceServerRepository::class),
    'MyUserProvider' => autowire(UserProvider::class),

    Listener\ClientCreatedListener::class => create()
        ->tag('event_subscriber', ['subscribes_to' => Client\ClientCreatedEvent::class]),
    Listener\ClientDeletedListener::class => create()
        ->tag('event_subscriber', ['subscribes_to' => Client\ClientDeletedEvent::class]),
    Listener\ClientOwnerChangedListener::class => create()
        ->tag('event_subscriber', ['subscribes_to' => Client\ClientOwnerChangedEvent::class]),
    Listener\ClientParametersUpdatedListener::class => create()
        ->tag('event_subscriber', ['subscribes_to' => Client\ClientParametersUpdatedEvent::class]),

    Listener\AccessTokenCreatedListener::class => create()
        ->tag('event_subscriber', ['subscribes_to' => AccessToken\AccessTokenCreatedEvent::class]),
    Listener\AccessTokenRevokedListener::class => create()
        ->tag('event_subscriber', ['subscribes_to' => AccessToken\AccessTokenRevokedEvent::class]),

    Listener\RefreshTokenCreatedListener::class => create()
        ->tag('event_subscriber', ['subscribes_to' => RefreshToken\RefreshTokenCreatedEvent::class]),
    Listener\RefreshTokenRevokedListener::class => create()
        ->tag('event_subscriber', ['subscribes_to' => RefreshToken\RefreshTokenRevokedEvent::class]),

    Listener\AuthCodeCreatedListener::class => create()
        ->tag('event_subscriber', ['subscribes_to' => AuthCode\AuthCodeCreatedEvent::class]),
    Listener\AuthCodeRevokedListener::class => create()
        ->tag('event_subscriber', ['subscribes_to' => AuthCode\AuthCodeRevokedEvent::class]),
    Listener\AuthCodeMarkedAsUsedListener::class => create()
        ->tag('event_subscriber', ['subscribes_to' => AuthCode\AuthCodeMarkedAsUsedEvent::class]),

    AccessTokenHandler::class => autowire()
        ->tag('oauth2_server_access_token_handler'),

    'MyPairwiseSubjectIdentifier' => create(EncryptedSubjectIdentifier::class)
        ->arguments(
            'This is my secret Key !!!',
            'aes-128-cbc',
            mb_substr('This is my salt or my IV !!!', 0, 16, '8bit'),
            mb_substr('This is my salt or my IV !!!', 0, 16, '8bit')
        ),

    'MyResourceRepository' => create(ResourceRepository::class),

    ResourceServerAuthMethodByIpAddress::class => create()
        ->tag('token_introspection_endpoint_auth_method'),
];
