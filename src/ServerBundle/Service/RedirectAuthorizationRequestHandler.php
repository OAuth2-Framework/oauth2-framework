<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Service;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\RouterInterface;

class RedirectAuthorizationRequestHandler implements AuthorizationRequestHandler
{
    public function __construct(
        private RouterInterface $router,
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function handle(ServerRequestInterface $request, string $authorizationId): ?ResponseInterface
    {
        $route = $this->router->generate('oauth2_server_authorization_endpoint', [
            'authorization_request_id' => $authorizationId,
        ]);
        $response = $this->responseFactory->createResponse(307);

        return $response->withHeader('location', $route);
    }
}
