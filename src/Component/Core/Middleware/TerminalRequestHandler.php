<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TerminalRequestHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct()
    {
        $this->responseFactory = new Psr17Factory();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createResponse();
    }
}
