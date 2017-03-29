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

namespace OAuth2Framework\Component\Server\ResponseMode;

use Psr\Http\Message\ResponseInterface;

interface ResponseModeInterface
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * @param string $redirectUri
     * @param array  $data
     *
     * @return ResponseInterface
     */
    public function buildResponse(string $redirectUri, array $data): ResponseInterface;
}
