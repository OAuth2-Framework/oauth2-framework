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

use OAuth2Framework\Bundle\Server\Model\PreConfiguredAuthorizationRepository;
use OAuth2Framework\Component\Server\Command\PreConfiguredAuthorization;
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

    PreConfiguredAuthorizationRepository::class => create()
        ->arguments(
            get('oauth2_server.endpoint.authorization.pre_configured_authorization.event_store'),
            get('event_recorder'),
            get('cache.app')
        ),

    PreConfiguredAuthorization\CreatePreConfiguredAuthorizationCommandHandler::class => create()
        ->arguments(
            get(PreConfiguredAuthorizationRepository::class)
        )
        ->tag('command_handler', ['handles' => PreConfiguredAuthorization\CreatePreConfiguredAuthorizationCommand::class]),

    PreConfiguredAuthorization\RevokePreConfiguredAuthorizationCommandHandler::class => create()
        ->arguments(
            get(PreConfiguredAuthorizationRepository::class)
        )
        ->tag('command_handler', ['handles' => PreConfiguredAuthorization\RevokePreConfiguredAuthorizationCommand::class]),
];
