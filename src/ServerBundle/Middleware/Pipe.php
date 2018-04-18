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

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Pipe implements MiddlewareInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    /**
     * Dispatcher constructor.
     *
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(array $middlewares = [])
    {
        $this->middlewares = $middlewares;
    }

    /**
     * Appends new middleware for this message bus. Should only be used at configuration time.
     *
     * @param MiddlewareInterface $middleware
     */
    public function appendMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Prepends new middleware for this message bus. Should only be used at configuration time.
     *
     * @param MiddlewareInterface $middleware
     */
    public function prependMiddleware(MiddlewareInterface $middleware)
    {
        array_unshift($this->middlewares, $middleware);
    }

    /**
     * @param MiddlewareInterface $middleware
     */
    public function addMiddlewareAfterFirstOne(MiddlewareInterface $middleware)
    {
        $count = count($this->middlewares);
        $temp = array_slice($this->middlewares, 1, $count);
        array_unshift($temp, $middleware);
        array_unshift($temp, $this->middlewares[0]);
        $this->middlewares = $temp;
    }

    /**
     * @param MiddlewareInterface $middleware
     */
    public function addMiddlewareBeforeLastOne(MiddlewareInterface $middleware)
    {
        $count = count($this->middlewares);
        $temp = array_slice($this->middlewares, 0, $count - 1);
        $temp[] = $middleware;
        $temp[] = $this->middlewares[$count - 1];
        $this->middlewares = $temp;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->middlewares[] = new RequestHandler(function (ServerRequestInterface $request) use ($handler) {
            return $handler->handle($request);
        });

        $response = $this->dispatch($request);

        array_pop($this->middlewares);

        return $response;
    }

    /**
     * Dispatches the middleware and returns the resulting `ResponseInterface`.
     *
     * @param ServerRequestInterface $request
     *
     * @throws \LogicException on unexpected result from any middleware on the middlewares
     *
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $resolved = $this->resolve(0);

        return $resolved->handle($request);
    }

    /**
     * @param int $index Middleware index
     *
     * @return RequestHandlerInterface
     */
    private function resolve(int $index): RequestHandlerInterface
    {
        if (isset($this->middlewares[$index])) {
            $middleware = $this->middlewares[$index];

            return new RequestHandler(function (ServerRequestInterface $request) use ($middleware, $index) {
                return $middleware->process($request, $this->resolve($index + 1));
            });
        }

        return new RequestHandler(function () {
            throw new \LogicException('Unresolved request: middleware exhausted with no result.');
        });
    }
}
