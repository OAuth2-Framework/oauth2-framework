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
use Zend\Diactoros\Response;

/**
 * Class MessageFactory.
 */
final class MessageFactory implements ResponseFactory
{
    /**
     * {@inheritdoc}
     */
    public function createResponse($statusCode = 200, $reasonPhrase = null, array $header = [], $body = null, $protocolVersion = '1.1')
    {
        $body = null === $body ? 'php://memory' : $body;
        $response = new Response($body, $statusCode, $header);
        $response = $response->withProtocolVersion($protocolVersion);
        if (is_string($reasonPhrase)) {
            $response = $response->withStatus($statusCode, $reasonPhrase);
        }

        return $response;
    }
}
