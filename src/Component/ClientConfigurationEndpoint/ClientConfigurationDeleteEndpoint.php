<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientConfigurationEndpoint;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ClientConfigurationDeleteEndpoint implements MiddlewareInterface
{
    public function __construct(
        private ClientRepository $clientRepository,
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        /** @var Client $client */
        $client = $request->getAttribute('client');
        $client->markAsDeleted();
        $this->clientRepository->save($client);

        $response = $this->responseFactory->createResponse(204);
        $response = $response->withHeader('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate, private');

        return $response->withHeader('Pragma', 'no-cache, no-store');
    }
}
