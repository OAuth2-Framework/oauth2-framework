<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TerminalRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $requestFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->requestFactory->createResponse();
    }
}
