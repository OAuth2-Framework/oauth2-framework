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

namespace OAuth2Framework\SecurityBundle\Service;

use Http\Message\ResponseFactory;
use Http\Message\RequestFactory;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;

/**
 * Class MessageFactory.
 */
final class MessageFactory implements ResponseFactory, RequestFactory
{
    /**
     * {@inheritdoc}
     */
    public function createRequest($method, $uri, array $header = [], $body = null, $protocolVersion = '1.1')
    {
        $body = $body === null ? 'php://temp' : $body;
        $request = new Request($uri, $method, $body, $header);
        $request = $request->withProtocolVersion($protocolVersion);

        return $request;
    }

    /**
     * {@inheritdoc}
     */
    public function createResponse($statusCode = 200, $reasonPhrase = null, array $header = [], $body = null, $protocolVersion = '1.1')
    {
        $body = $body === null ? 'php://memory' : $body;
        $response = new Response($body, $statusCode, $header);
        $response = $response->withProtocolVersion($protocolVersion);
        if (is_string($reasonPhrase)) {
            $response = $response->withStatus($statusCode, $reasonPhrase);
        }

        return $response;
    }
}
