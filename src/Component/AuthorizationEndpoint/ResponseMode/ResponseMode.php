<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

use Psr\Http\Message\ResponseInterface;

interface ResponseMode
{
    public function name(): string;

    public function buildResponse(ResponseInterface $response, string $redirectUri, array $data): ResponseInterface;
}
