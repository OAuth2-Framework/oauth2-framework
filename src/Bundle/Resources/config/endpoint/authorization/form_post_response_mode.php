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

use OAuth2Framework\Bundle\Service\TwigFormPostResponseRenderer;
use OAuth2Framework\Component\ResponseMode;
use function Fluent\create;
use function Fluent\get;

return [
    TwigFormPostResponseRenderer::class => create()
        ->arguments(
            get('templating'),
            '%oauth2_server.endpoint.authorization.response_mode.form_post.template%'
        ),

    ResponseMode\FormPostResponseMode::class => create()
        ->arguments(
            get(TwigFormPostResponseRenderer::class),
            get('httplug.message_factory')
        )
        ->tag('oauth2_server_response_mode'),
];
