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
use OAuth2Framework\Component\Server\Endpoint\Metadata\Metadata;
use OAuth2Framework\Component\Server\Endpoint\Metadata\MetadataEndpoint;
use function Fluent\create;
use function Fluent\get;

return [
    Metadata::class => create(),

    /*
    oauth2_server.openid_connect.metadata:
        class: 'OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\Service\Metadata'
        arguments:
            - '@router'

     */
    MetadataEndpoint::class => create()
        ->arguments(
            get('oauth2_server.http.response_factory'),
            get(Metadata::class)
        ),
];
