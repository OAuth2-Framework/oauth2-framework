<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Service;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentHandler as ConsentHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ConsentHandler implements ConsentHandlerInterface
{
    private readonly ResponseFactoryInterface $responseFactory;

    public function __construct()
    {
        $this->responseFactory = new Psr17Factory();
    }

    public function handle(ServerRequestInterface $request, string $authorizationId): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()
            ->write('You are on the consent page')
        ;
        $response->getBody()
            ->rewind()
        ;

        return $response;
    }
}
