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

use OAuth2Framework\Bundle\Server\Service\FormPostResponseRenderer;
use OAuth2Framework\Component\Server\ResponseMode;
use function Fluent\create;
use function Fluent\get;

return [
    FormPostResponseRenderer::class => create()
        ->arguments(
            get('templating'),
            '%oauth2_server.endpoint.authorization.response_mode.form_post.template%'
        ),

    ResponseMode\FormPostResponseMode::class => create()
        ->arguments(
            get(FormPostResponseRenderer::class),
            get('httplug.message_factory')
        )
        ->tag('oauth2_server_response_mode'),
];
