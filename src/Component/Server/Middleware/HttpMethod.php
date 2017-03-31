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

namespace OAuth2Framework\Component\Server\Middleware;

use Assert\Assertion;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

final class HttpMethod implements MiddlewareInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $methodMap = [];

    /**
     * @param string              $method
     * @param MiddlewareInterface $middleware
     */
    public function addMiddleware(string $method, MiddlewareInterface $middleware)
    {
        Assertion::keyNotExists($this->methodMap, $method, sprintf('The method \'%s\' is already defined.', $method));
        $this->methodMap[$method] = $middleware;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $method = $request->getMethod();

        if (!array_key_exists($method, $this->methodMap)) {
            throw new OAuth2Exception(
                405,
                [
                    'error' => 'not_implemented',
                    'error_description' => sprintf('The method \'%s\' is not supported.', $method),
                ]
            );
        }

        $middleware = $this->methodMap[$method];

        return $middleware->process($request, $delegate);
    }
}
