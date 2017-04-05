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

namespace OAuth2Framework\Component\Server\Response;

use Psr\Http\Message\ResponseInterface;

interface OAuth2ResponseInterface
{
    /**
     * Get the response code.
     *
     * @return int
     */
    public function getCode(): int;

    /**
     * Get the OAuth2 Response as a PSR-7 Response object.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface;

    /**
     * @return array
     */
    public function getData();
}
