<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Service;

use OAuth2Framework\Component\AuthorizationEndpoint\LoginHandler as LoginHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LoginHandler implements LoginHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function handle(ServerRequestInterface $request, string $authorizationId): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()
            ->write('YOU ARE ON THE LOGIN PAGE')
        ;
        $response->getBody()
            ->rewind()
        ;

        return $response;
    }
}
