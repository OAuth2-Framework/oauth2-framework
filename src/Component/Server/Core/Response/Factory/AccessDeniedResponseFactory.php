<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Core\Response\Factory;

use OAuth2Framework\Component\Server\Core\Response\OAuth2Error;
use OAuth2Framework\Component\Server\Core\Response\OAuth2ResponseInterface;
use Psr\Http\Message\ResponseInterface;

final class AccessDeniedResponseFactory implements ResponseFactory
{
    /**
     * {@inheritdoc}
     */
    public function getSupportedCode(): int
    {
        return 403;
    }

    /**
     * {@inheritdoc}
     */
    public function createResponse(array $data, ResponseInterface &$response): OAuth2ResponseInterface
    {
        return new OAuth2Error(403, $data, $response);
    }
}
