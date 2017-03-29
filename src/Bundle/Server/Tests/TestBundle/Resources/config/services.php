<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Bundle\Server\Tests\TestBundle\Entity\ResourceRepository;
use OAuth2Framework\Bundle\Server\Tests\TestBundle\Entity\UserManager;
use OAuth2Framework\Bundle\Server\Tests\TestBundle\Entity\UserRepository;
use OAuth2Framework\Bundle\Server\Tests\TestBundle\Listener;
use OAuth2Framework\Bundle\Server\Tests\TestBundle\Service\AccessTokenHandler;
use OAuth2Framework\Bundle\Server\Tests\TestBundle\Service\UserProvider;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\Pairwise\EncryptedSubjectIdentifier;
use OAuth2Framework\Component\Server\Event\AccessToken;
use OAuth2Framework\Component\Server\Event\AuthCode;
use OAuth2Framework\Component\Server\Event\Client;
use OAuth2Framework\Component\Server\Event\RefreshToken;
use OAuth2Framework\Component\Server\Tests\Stub\ResourceServerRepository;
use function Fluent\create;
use function Fluent\get;

return [
    'oauth2_server.event_store.access_token' => create(\OAuth2Framework\Bundle\Server\Tests\TestBundle\Service\EventStore::class)
        ->arguments(
            '%kernel.cache_dir%',
            'access_token'
        ),

    OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface::class => create(OAuth2Framework\Bundle\Server\Model\AccessTokenRepository::class) //Fixme
        ->arguments(
            get('oauth2_server.event_store.access_token'),
            get('event_recorder'),
            1800
        ),

    OAuth2Framework\Component\Server\Model\UserAccount\UserAccountManagerInterface::class => create(UserManager::class),

    OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface::class => create(UserRepository::class),

    OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerRepositoryInterface::class => create(ResourceServerRepository::class),

    'oauth2_server.test_bundle.user_provider' => create(UserProvider::class)
        ->arguments(
            get(OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface::class)
        ),

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

    AccessTokenHandler::class => create()
        ->arguments(
            get(OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface::class)
        )
        ->tag('oauth2_server_access_token_handler'),

    'pairwise_subject_identifier' => create(EncryptedSubjectIdentifier::class)
        ->arguments(
            'This is my secret Key !!!',
            'aes-128-cbc',
            mb_substr('This is my salt or my IV !!!', 0, 16, '8bit'),
            mb_substr('This is my salt or my IV !!!', 0, 16, '8bit')
        ),

    ResourceRepository::class => create(),

    \OAuth2Framework\Component\Server\Tests\Stub\ResourceServerAuthMethodByIpAddress::class => create()
        ->tag('token_introspection_endpoint_auth_method'),
];
