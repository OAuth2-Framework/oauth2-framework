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

use OAuth2Framework\Component\Server\Command\AccessToken;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenRepositoryInterface;
use OAuth2Framework\Component\Server\TokenTypeHint\AccessTokenTypeHint;
use function Fluent\create;
use function Fluent\get;

return [
    AccessToken\CreateAccessTokenCommandHandler::class => create()
        ->arguments(
            get(AccessTokenRepositoryInterface::class)
        )
        ->tag('command_handler', ['handles' => AccessToken\CreateAccessTokenCommand::class]),

    AccessToken\CreateAccessTokenWithRefreshTokenCommandHandler::class => create()
        ->arguments(
            get(AccessTokenRepositoryInterface::class),
            get(RefreshTokenRepositoryInterface::class)->nullIfMissing()
        )
        ->tag('command_handler', ['handles' => AccessToken\CreateAccessTokenWithRefreshTokenCommand::class]),

    AccessToken\RevokeAccessTokenCommandHandler::class => create()
        ->arguments(
            get(AccessTokenRepositoryInterface::class)
        )
        ->tag('command_handler', ['handles' => AccessToken\RevokeAccessTokenCommand::class]),

    AccessTokenTypeHint::class => create()
        ->arguments(
            get(AccessTokenRepositoryInterface::class),
            get('command_bus')
        )
        ->tag('oauth2_server_token_type_hint'),
];
