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

use Http\Factory\Diactoros\RequestFactory;
use Http\Factory\Diactoros\ResponseFactory;
use Http\Factory\Diactoros\UriFactory;
use function Fluent\create;

return [
    'oauth2_server.http.client' => create(Http\Mock\Client::class),
    'oauth2_server.http.request_factory' => create(RequestFactory::class),
    'oauth2_server.http.response_factory' => create(ResponseFactory::class),
    'oauth2_server.http.uri_factory' => create(UriFactory::class),
];
