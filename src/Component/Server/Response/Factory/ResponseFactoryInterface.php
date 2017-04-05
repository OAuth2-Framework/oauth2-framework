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

namespace OAuth2Framework\Component\Server\Response\Factory;

use OAuth2Framework\Component\Server\Response\OAuth2ResponseInterface;
use Psr\Http\Message\ResponseInterface;

interface ResponseFactoryInterface
{
    /**
     * @return int
     */
    public function getSupportedCode(): int;

    /**
     * @param array             $data     Data sent to the response
     * @param ResponseInterface $response PSR-7 Response to be populated
     *
     * @return OAuth2ResponseInterface
     */
    public function createResponse(array $data, ResponseInterface &$response): OAuth2ResponseInterface;
}
