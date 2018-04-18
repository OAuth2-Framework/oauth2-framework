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

namespace OAuth2Framework\ServerBundle\Middleware;

use OAuth2Framework\Component\Core\Message\OAuth2Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HttpMethodMiddleware implements MiddlewareInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $methodMap = [];

    /**
     * @param string              $method
     * @param MiddlewareInterface $middleware
     */
    public function add(string $method, MiddlewareInterface $middleware)
    {
        if (array_key_exists($method, $this->methodMap)) {
            throw new \InvalidArgumentException(sprintf('The method "%s" is already defined.', $method));
        }
        $this->methodMap[$method] = $middleware;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();

        if (!array_key_exists($method, $this->methodMap)) {
            throw new OAuth2Message(
                405,
                'not_implemented',
                sprintf('The method "%s" is not supported.', $method)
            );
        }

        $middleware = $this->methodMap[$method];

        return $middleware->process($request, $handler);
    }
}