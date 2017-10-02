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

use OAuth2Framework\Component\Server\ResponseMode;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\ResponseTypeAndResponseModeParameterChecker;
use function Fluent\create;
use function Fluent\get;

return [
    ResponseMode\ResponseModeManager::class => create(),

    ResponseMode\QueryResponseMode::class => create()
        ->arguments(
            get('oauth2_server.http.uri_factory'),
            get('httplug.message_factory')
        )
        ->tag('oauth2_server_response_mode'),

    ResponseMode\FragmentResponseMode::class => create()
        ->arguments(
            get('oauth2_server.http.uri_factory'),
            get('httplug.message_factory')
        )
        ->tag('oauth2_server_response_mode'),

    ResponseTypeAndResponseModeParameterChecker::class => create()
        ->arguments(
            get(ResponseTypeManager::class),
            get(ResponseMode\ResponseModeManager::class),
            'oauth2_server.endpoint.authorization.response_mode.allow_response_mode_parameter'
        )
        ->tag('oauth2_server_authorization_parameter_checker'),
];
