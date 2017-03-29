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

use OAuth2Framework\Component\Server\Response\OAuth2Error;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseInterface;
use Psr\Http\Message\ResponseInterface;

final class MethodNotAllowedResponseFactory implements ResponseFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSupportedCode(): int
    {
        return 405;
    }

    /**
     * {@inheritdoc}
     */
    public function createResponse(array $data, ResponseInterface &$response): OAuth2ResponseInterface
    {
        return new OAuth2Error(405, $data, $response);
    }
}
