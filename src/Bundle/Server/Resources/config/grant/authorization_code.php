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

use OAuth2Framework\Bundle\Server\Model\AuthCodeRepository;
use OAuth2Framework\Component\Server\Command\AuthCode;
use OAuth2Framework\Component\Server\GrantType\AuthorizationCodeGrantType;
use OAuth2Framework\Component\Server\GrantType\PKCEMethod;
use OAuth2Framework\Component\Server\ResponseType\CodeResponseType;
use OAuth2Framework\Component\Server\TokenTypeHint\AuthCodeTypeHint;
use function Fluent\create;
use function Fluent\get;

return [
    /*'oauth2_server.event_store.auth_code' => create(OAuth2Framework\Bundle\Server\EventStore\EventStore::class)
        ->arguments(
            get('cache.app')
        ),*/
    'oauth2_server.event_store.auth_code' => create(\OAuth2Framework\Bundle\Server\Tests\TestBundle\Service\EventStore::class)
        ->arguments(
            '%kernel.cache_dir%',
            'auth_code'
        ),

    AuthCodeRepository::class => create()
        ->arguments(
            '%oauth2_server.grant.authorization_code.min_length%',
            '%oauth2_server.grant.authorization_code.max_length%',
            '%oauth2_server.grant.authorization_code.lifetime%',
            get('oauth2_server.event_store.auth_code'),
            get('event_recorder')
        ),

    AuthorizationCodeGrantType::class => create()
        ->arguments(
            get(AuthCodeRepository::class),
            get(PKCEMethod\PKCEMethodManager::class),
            get('command_bus')
        )
        ->tag('oauth2_server_grant_type'),

    CodeResponseType::class => create()
        ->arguments(
            get('command_bus'),
            get(PKCEMethod\PKCEMethodManager::class),
            '%oauth2_server.grant.authorization_code.enforce_pkce%'
        )
        ->tag('oauth2_server_response_type'),

    PKCEMethod\PKCEMethodManager::class => create(),

    PKCEMethod\Plain::class => create()
        ->tag('oauth2_server_pkce_method', ['alias' => 'plain']),

    PKCEMethod\S256::class => create()
        ->tag('oauth2_server_pkce_method', ['alias' => 'S256']),

    // Commands
    AuthCode\CreateAuthCodeCommandHandler::class => create()
        ->arguments(
            get(AuthCodeRepository::class)
        )
        ->tag('command_handler', ['handles' => AuthCode\CreateAuthCodeCommand::class]),

    AuthCode\MarkAuthCodeAsUsedCommandHandler::class => create()
        ->arguments(
            get(AuthCodeRepository::class)
        )
        ->tag('command_handler', ['handles' => AuthCode\MarkAuthCodeAsUsedCommand::class]),

    AuthCode\RevokeAuthCodeCommandHandler::class => create()
        ->arguments(
            get(AuthCodeRepository::class)
        )
        ->tag('command_handler', ['handles' => AuthCode\RevokeAuthCodeCommand::class]),

    // For token introspection and revocation
    AuthCodeTypeHint::class => create()
        ->arguments(
            get(AuthCodeRepository::class),
            get('command_bus')
        )
        ->tag('oauth2_server_token_type_hint'),
];
