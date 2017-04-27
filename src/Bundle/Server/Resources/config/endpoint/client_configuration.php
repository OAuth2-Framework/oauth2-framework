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

use OAuth2Framework\Bundle\Server\Controller\ClientConfigurationMiddleware;
use OAuth2Framework\Bundle\Server\Model\ClientRepository;
use OAuth2Framework\Component\Server\Endpoint\ClientConfiguration\ClientConfigurationEndpoint;
use OAuth2Framework\Component\Server\Middleware\OAuth2ResponseMiddleware;
use OAuth2Framework\Component\Server\Middleware\Pipe;
use OAuth2Framework\Component\Server\TokenType\BearerToken;
use function Fluent\create;
use function Fluent\get;
use OAuth2Framework\Component\Server\Middleware\JsonBodyParserMiddleware;

return [
    'client_configuration_endpoint_pipe' => create(Pipe::class)
        ->arguments([
            get(OAuth2ResponseMiddleware::class),
            get(JsonBodyParserMiddleware::class),
            get(ClientConfigurationMiddleware::class),
            get(ClientConfigurationEndpoint::class),
        ]),

    'client_configuration_bearer_token' => create(BearerToken::class)
        ->arguments(
            '%oauth2_server.endpoint.client_configuration.realm%',
            '%oauth2_server.endpoint.client_configuration.authorization_header%',
            '%oauth2_server.endpoint.client_configuration.request_body%',
            '%oauth2_server.endpoint.client_configuration.query_string%'
        ),

    ClientConfigurationMiddleware::class => create()
        ->arguments(
            get(ClientRepository::class)
        ),

    ClientConfigurationEndpoint::class => create()
        ->arguments(
            get('client_configuration_bearer_token'),
            get('command_bus'),
            get('oauth2_server.http.response_factory')
        ),
];
