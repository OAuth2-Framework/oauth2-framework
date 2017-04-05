<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Bundle\Server\Service\IssuerDiscoveryFactory;
use function Fluent\create;
use function Fluent\get;

return [
    IssuerDiscoveryFactory::class => create()
        ->arguments(
            get('oauth2_server.http.response_factory'),
            get('oauth2_server.http.uri_factory')
        ),
];
