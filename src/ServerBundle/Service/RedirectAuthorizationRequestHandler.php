<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Service;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\RouterInterface;

class RedirectAuthorizationRequestHandler implements AuthorizationRequestHandler
{
    private RouterInterface $router;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(RouterInterface $router, ResponseFactoryInterface $responseFactory)
    {
        $this->router = $router;
        $this->responseFactory = $responseFactory;
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
