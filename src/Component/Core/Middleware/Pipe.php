<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Middleware;

use function array_slice;
use function count;
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

    public function push(MiddlewareInterface $middleware): static
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function prepend(MiddlewareInterface $middleware): static
    {
        array_unshift($this->middlewares, $middleware);

        return $this;
    }

    public function addAfterFirstOne(MiddlewareInterface $middleware): static
    {
        $count = count($this->middlewares);
        if ($count === 0) {
            $this->middlewares[] = $middleware;

            return $this;
        }
        $temp = array_slice($this->middlewares, 1, $count);
        array_unshift($temp, $middleware);
        array_unshift($temp, $this->middlewares[0]);
        $this->middlewares = $temp;

        return $this;
    }

    public function addBeforeLastOne(MiddlewareInterface $middleware): static
    {
        $count = count($this->middlewares);
        if ($count === 0) {
            $this->middlewares[] = $middleware;

            return $this;
        }
        $temp = array_slice($this->middlewares, 0, $count - 1);
        $temp[] = $middleware;
        $temp[] = $this->middlewares[$count - 1];
        $this->middlewares = $temp;

        return $this;
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
