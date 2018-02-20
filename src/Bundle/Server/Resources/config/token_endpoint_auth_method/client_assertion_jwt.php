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

use OAuth2Framework\Bundle\Server\TokenEndpointAuthMethod\ClientAssertionJwt;
use function Fluent\create;
use function Fluent\get;

return [
    ClientAssertionJwt::class => create()
        ->arguments(
            get('jose.jws_loader.client_assertion_jwt'),
            get('jose.claim_checker.client_assertion_jwt'),
            '%oauth2_server.token_endpoint_auth_method.client_assertion_jwt.secret_lifetime%'
        )
        ->tag('oauth2_server_token_endpoint_auth_method'),
];
