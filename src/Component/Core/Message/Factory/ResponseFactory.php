<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Core\Message\Factory;

use Psr\Http\Message\ResponseInterface;

interface ResponseFactory
{
    public function getSupportedCode(): int;

    /**
     * @param array $data Data sent to the response
     */
    public function createResponse(array $data, ResponseInterface $response): ResponseInterface;
}
