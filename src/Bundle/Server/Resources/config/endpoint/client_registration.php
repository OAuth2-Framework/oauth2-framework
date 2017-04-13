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

use OAuth2Framework\Bundle\Server\Rule\ClientRegistrationManagementRule;
use OAuth2Framework\Component\Server\Endpoint\ClientRegistration\ClientRegistrationEndpoint;
use OAuth2Framework\Component\Server\Middleware\OAuth2ResponseMiddleware;
use OAuth2Framework\Component\Server\Middleware\Pipe;
use function Fluent\autowire;
use function Fluent\create;
use function Fluent\get;

return [
    'client_registration_endpoint_pipe' => create(Pipe::class)
        ->arguments([
            get(OAuth2ResponseMiddleware::class),
            get(ClientRegistrationEndpoint::class),
        ]),

    ClientRegistrationEndpoint::class => create()
        ->arguments(
            get('oauth2_server.http.response_factory'),
            get('command_bus')
        ),

    ClientRegistrationManagementRule::class => autowire()
        ->tag('oauth2_server_client_rule'),
];
