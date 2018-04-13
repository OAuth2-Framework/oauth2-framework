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

namespace OAuth2Framework\IssuerDiscoveryBundle\Tests\TestBundle\Service;

use GuzzleHttp\Psr7\Response;
use Http\Message\ResponseFactory as Base;

class ResponseFactory implements Base
{
    /**
     * {@inheritdoc}
     */
    public function createResponse($statusCode = 200, $reasonPhrase = null, array $header = [], $body = null, $protocolVersion = '1.1')
    {
        return new Response($statusCode, $header, $body, $protocolVersion, $reasonPhrase);
    }
}
