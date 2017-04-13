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
use function Fluent\autowire;
use function Fluent\create;
use function Fluent\get;

return [
    ResponseMode\ResponseModeManager::class => create(),

    ResponseMode\QueryResponseMode::class => autowire()
        ->tag('oauth2_server_response_mode'),

    ResponseMode\FragmentResponseMode::class => autowire()
        ->tag('oauth2_server_response_mode'),

    FormPostResponseRenderer::class => create()
        ->arguments(
            get('templating'),
            '@OAuth2FrameworkServerBundle/form_post/response.html.twig' //'%oauth2_server.form_post_response_mode.template%' FIXME
        ),

    ResponseMode\FormPostResponseMode::class => autowire()
        ->tag('oauth2_server_response_mode'),
];
