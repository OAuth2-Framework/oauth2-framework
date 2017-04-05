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

use OAuth2Framework\Bundle\Server\Model\InitialAccessTokenRepository;
use OAuth2Framework\Component\Server\Middleware\InitialAccessTokenMiddleware;
use OAuth2Framework\Component\Server\TokenType\BearerToken;
use function Fluent\create;
use function Fluent\get;

return [
    InitialAccessTokenRepository::class => create()
        ->arguments(
            '%oauth2_server.endpoint.client_registration.initial_access_token.min_length%',
            '%oauth2_server.endpoint.client_registration.initial_access_token.max_length%',
            get('oauth2_server.endpoint.client_registration.initial_access_token.event_store'),
            get('event_recorder')
        ),

    'client_registration_bearer_token' => create(BearerToken::class)
        ->arguments(
            '%oauth2_server.endpoint.client_registration.initial_access_token.realm%',
            '%oauth2_server.endpoint.client_registration.initial_access_token.authorization_header%',
            '%oauth2_server.endpoint.client_registration.initial_access_token.request_body%',
            '%oauth2_server.endpoint.client_registration.initial_access_token.query_string%'
        ),

    InitialAccessTokenMiddleware::class => create()
        ->arguments(
            get('client_registration_bearer_token'),
            get(InitialAccessTokenRepository::class)
        ),
];
