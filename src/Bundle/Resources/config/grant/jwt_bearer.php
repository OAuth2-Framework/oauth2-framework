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

use OAuth2Framework\Bundle\Model\ClientRepository;
use OAuth2Framework\Component\GrantType\JWTBearerGrantType;
use function Fluent\create;
use function Fluent\get;

return [
    JWTBearerGrantType::class => create()
        ->arguments(
            get('jose.jws_loader.jwt_bearer'),
            get('jose.claim_checker.jwt_bearer'),
            get(ClientRepository::class),
            get('oauth2_server.user_account.repository')
        )
        ->tag('oauth2_server_grant_type'),
];