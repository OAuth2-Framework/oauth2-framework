<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Controller;

use function array_key_exists;
use function is_array;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ClientConfigurationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ClientRepository $clientRepository
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeParameters = $request->getAttribute('_route_params');
        if (! is_array($routeParameters) || ! array_key_exists(
            'client_id',
            $routeParameters
        ) || null === $client = $this->clientRepository->find(new ClientId($routeParameters['client_id']))) {
            throw OAuth2Error::invalidRequest('Invalid client or invalid registration access token.');
        }
        $request = $request->withAttribute('client', $client);

        return $handler->handle($request);
    }
}
