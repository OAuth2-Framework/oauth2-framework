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

namespace OAuth2Framework\Component\ResourceServerAuthentication;

use Assert\Assertion;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    private AuthenticationMethodManager $authenticationMethodManager;

    private ResourceServerRepository $resourceServerRepository;

    public function __construct(ResourceServerRepository $resourceServerRepository, AuthenticationMethodManager $authenticationMethodManager)
    {
        $this->resourceServerRepository = $resourceServerRepository;
        $this->authenticationMethodManager = $authenticationMethodManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $authentication_method = null;
            $resourceServer_credentials = null;
            $resourceServerId = $this->authenticationMethodManager->findResourceServerIdAndCredentials($request, $authentication_method, $resourceServer_credentials);
            if (null !== $resourceServerId && $authentication_method instanceof AuthenticationMethod) {
                $resourceServer = $this->resourceServerRepository->find($resourceServerId);
                Assertion::notNull($resourceServer, 'Unknown resource server or resource server not authenticated.');
                $this->checkAuthenticationMethod($request, $resourceServer, $authentication_method, $resourceServer_credentials);
                $request = $request->withAttribute('resource_server', $resourceServer);
                $request = $request->withAttribute('resource_server_authentication_method', $authentication_method);
                $request = $request->withAttribute('resource_server_credentials', $resourceServer_credentials);
            }
        } catch (\Throwable $e) {
            throw new OAuth2Error(401, OAuth2Error::ERROR_INVALID_RESOURCE_SERVER, $e->getMessage(), [], $e);
        }

        return $handler->handle($request);
    }

    /**
     * @param mixed $resourceServerCredentials
     */
    private function checkAuthenticationMethod(ServerRequestInterface $request, ResourceServer $resourceServer, AuthenticationMethod $authenticationMethod, $resourceServerCredentials): void
    {
        if (!\in_array($resourceServer->getAuthenticationMethod(), $authenticationMethod->getSupportedMethods(), true)) {
            throw new \InvalidArgumentException('Unknown resource server or resource server not authenticated.');
        }
        if (!$authenticationMethod->isResourceServerAuthenticated($resourceServer, $resourceServerCredentials, $request)) {
            throw new \InvalidArgumentException('Unknown resource server or resource server not authenticated.');
        }
    }
}
