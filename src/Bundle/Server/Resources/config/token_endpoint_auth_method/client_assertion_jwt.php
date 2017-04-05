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

use OAuth2Framework\Bundle\Server\TokenEndpointAuthMethod\ClientAssertionJwt;
use function Fluent\create;
use function Fluent\get;

return [
    ClientAssertionJwt::class => create()
        ->arguments(
            get('jose.jwt_loader.client_assertion_jwt'),
            0 // FixMe: Secret lifetime
        )
        ->tag('oauth2_server_token_endpoint_auth_method'),
];
