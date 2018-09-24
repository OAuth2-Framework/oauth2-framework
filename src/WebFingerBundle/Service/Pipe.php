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

namespace OAuth2Framework\WebFingerBundle\Service;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Pipe implements MiddlewareInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    /**
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(array $middlewares = [])
    {
        $this->middlewares = $middlewares;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->middlewares[] = new RequestHandler(function (ServerRequestInterface $request) use ($handler) {
            return $handler->handle($request);
        });

        $response = $this->dispatch($request);

        \array_pop($this->middlewares);

        return $response;
    }

    /**
     * Dispatches the middleware and returns the resulting `ResponseInterface`.
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $resolved = $this->resolve(0);

        return $resolved->handle($request);
    }

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
