<?php

declare(strict_types=1);

namespace OAuth2Framework\WebFingerBundle\Middleware;

use Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Pipe implements MiddlewareInterface
{
    public function __construct(
        private array $middlewares = []
    ) {
    }

    public function push(MiddlewareInterface $value): void
    {
        $this->middlewares[] = $value;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return (new Consumer($this->getGenerator(), $handler))->handle($request);
    }

    private function getGenerator(): Generator
    {
        yield from $this->middlewares;
    }
}
