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

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\UriFactoryInterface;
use OAuth2Framework\Component\Server\ResponseMode;
use function Fluent\create;
use function Fluent\get;
use OAuth2Framework\Bundle\Server\Service\FormPostResponseRenderer;

return [
    ResponseMode\ResponseModeManager::class => create(),

    ResponseMode\QueryResponseMode::class => create()
        ->arguments(
            get(UriFactoryInterface::class),
            get(ResponseFactoryInterface::class)
        )
        ->tag('oauth2_server_response_mode'),

    ResponseMode\FragmentResponseMode::class => create()
        ->arguments(
            get(UriFactoryInterface::class),
            get(ResponseFactoryInterface::class)
        )
        ->tag('oauth2_server_response_mode'),

    FormPostResponseRenderer::class => create()
        ->arguments(
            get('templating'),
            '@OAuth2FrameworkServerBundle/form_post/response.html.twig' //'%oauth2_server.form_post_response_mode.template%' FIXME
        ),

    ResponseMode\FormPostResponseMode::class => create()
        ->arguments(
            get(FormPostResponseRenderer::class),
            get(ResponseFactoryInterface::class)
        )
        ->tag('oauth2_server_response_mode'),
];
