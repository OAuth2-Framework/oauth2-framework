<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Middleware;

use function array_key_exists;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HttpMethodMiddleware implements MiddlewareInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $methodMap = [];

    public function add(string $method, MiddlewareInterface $middleware): static
    {
        if (array_key_exists($method, $this->methodMap)) {
            throw new InvalidArgumentException(sprintf('The method "%s" is already defined.', $method));
        }
        $this->methodMap[$method] = $middleware;

        return $this;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();

        if (! array_key_exists($method, $this->methodMap)) {
            throw new OAuth2Error(405, 'not_implemented', sprintf('The method "%s" is not supported.', $method));
        }

        $middleware = $this->methodMap[$method];

        return $middleware->process($request, $handler);
    }
}
