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

use OAuth2Framework\Bundle\Server\Model\RefreshTokenRepository;
use OAuth2Framework\Component\Server\Command\RefreshToken;
use OAuth2Framework\Component\Server\GrantType\RefreshTokenGrantType;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenRepositoryInterface;
use OAuth2Framework\Component\Server\TokenTypeHint\RefreshTokenTypeHint;
use function Fluent\create;
use function Fluent\get;

return [
    RefreshTokenRepositoryInterface::class => create(RefreshTokenRepository::class)
        ->arguments(
            '%oauth2_server.grant.refresh_token.min_length%',
            '%oauth2_server.grant.refresh_token.max_length%',
            '%oauth2_server.grant.refresh_token.lifetime%',
            get('oauth2_server.grant.refresh_token.event_store'),
            get('event_bus'),
            get('cache.app')
        ),

    RefreshTokenGrantType::class => create()
        ->arguments(
            get(RefreshTokenRepositoryInterface::class)
        )
        ->tag('oauth2_server_grant_type'),

    RefreshTokenTypeHint::class => create()
        ->arguments(
            get(RefreshTokenRepositoryInterface::class),
            get('command_bus')
        )
        ->tag('oauth2_server_token_type_hint'),
];
