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

use OAuth2Framework\ServerBundle\Service\IFrameEndpoint;
use OAuth2Framework\ServerBundle\Service\SessionStateParameterExtension;
use OAuth2Framework\Component\Middleware\Pipe;
use function Fluent\create;
use function Fluent\get;

return [
    'session_management_pipe' => create(Pipe::class)
        ->arguments([
            get(IFrameEndpoint::class),
        ]),

    IFrameEndpoint::class => create()
        ->arguments(
            get('templating'),
            get('httplug.message_factory'),
            '%oauth2_server.endpoint.session_management.template%',
            '%oauth2_server.endpoint.session_management.storage_name%'
        ),

    SessionStateParameterExtension::class => create()
        ->arguments(
            get('session'),
            '%oauth2_server.endpoint.session_management.storage_name%'
        )
        ->tag('oauth2_server_after_consent_screen'),
];
