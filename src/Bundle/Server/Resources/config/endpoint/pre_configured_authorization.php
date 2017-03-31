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

use OAuth2Framework\Bundle\Server\Model\PreConfiguredAuthorizationRepository;
use OAuth2Framework\Component\Server\Endpoint\Authorization\AfterConsentScreen;
use OAuth2Framework\Component\Server\Endpoint\Authorization\BeforeConsentScreen;
use function Fluent\create;
use function Fluent\get;

return [
    BeforeConsentScreen\PreConfiguredAuthorizationExtension::class => create()
        ->arguments(
            get(PreConfiguredAuthorizationRepository::class)
        )
        ->tag('oauth2_server_before_consent_screen'),

    AfterConsentScreen\PreConfiguredAuthorizationExtension::class => create()
        ->arguments(
            get(PreConfiguredAuthorizationRepository::class)
        )
        ->tag('oauth2_server_after_consent_screen'),

    /*'oauth2_server.event_store.pre_configured_authorization' => create(OAuth2Framework\Bundle\Server\EventStore\EventStore::class)
        ->arguments(
            get('cache.app')
        ),*/
    'oauth2_server.event_store.pre_configured_authorization' => create(\OAuth2Framework\Bundle\Server\Tests\TestBundle\Service\EventStore::class)
        ->arguments(
            '%kernel.cache_dir%',
            'pre_configured_authorization'
        ),

    PreConfiguredAuthorizationRepository::class => create()
        ->arguments(
            get('oauth2_server.event_store.pre_configured_authorization'),
            get('event_recorder')
        ),
];
