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

use OAuth2Framework\Bundle\Rule\ClientRegistrationManagementRule;
use OAuth2Framework\Component\Endpoint\ClientRegistration\ClientRegistrationEndpoint;
use OAuth2Framework\Component\Middleware\OAuth2ResponseMiddleware;
use OAuth2Framework\Component\Middleware\Pipe;
use function Fluent\create;
use function Fluent\get;
use OAuth2Framework\Component\Middleware\JsonBodyParserMiddleware;

return [
    'client_registration_endpoint_pipe' => create(Pipe::class)
        ->arguments([
            get(OAuth2ResponseMiddleware::class),
            get(JsonBodyParserMiddleware::class),
            get(ClientRegistrationEndpoint::class),
        ]),

    ClientRegistrationEndpoint::class => create()
        ->arguments(
            get('httplug.message_factory'),
            get('command_bus')
        ),

    ClientRegistrationManagementRule::class => create()
        ->arguments(
            get('router')
        )
        ->tag('oauth2_server_client_rule'),
];
