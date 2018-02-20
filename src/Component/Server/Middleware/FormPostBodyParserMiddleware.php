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

namespace OAuth2Framework\Component\Server\Middleware;

use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FormPostBodyParserMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler)
    {
        $headers = $request->getHeader('content-type');
        foreach ($headers as $header) {
            if ('application/x-www-form-urlencoded' === substr($header, 0, 33)) {
                $request->getBody()->rewind();
                $body = $request->getBody()->getContents();
                if (true === parse_str($body, $parsed)) {
                    $request = $request->withParsedBody($parsed);
                }
            }
        }

        return $requestHandler->handle($request);
    }
}
