<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Middleware;

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class OAuth2MessageMiddleware implements MiddlewareInterface
{
    public function __construct(
        private OAuth2MessageFactoryManager $auth2messageFactoryManager
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (OAuth2Error $e) {
            return $this->auth2messageFactoryManager->getResponse($e);
        }
    }
}
