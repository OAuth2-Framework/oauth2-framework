<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\ResourceServerAuthentication;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerRepository;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @var AuthenticationMethodManager
     */
    private $authenticationMethodManager;

    /**
     * @var ResourceServerRepository
     */
    private $resourceServerRepository;

    /**
     * ResourceServerAuthenticationMiddleware constructor.
     *
     * @param ResourceServerRepository    $resourceServerRepository
     * @param AuthenticationMethodManager $authenticationMethodManager
     */
    public function __construct(ResourceServerRepository $resourceServerRepository, AuthenticationMethodManager $authenticationMethodManager)
    {
        $this->resourceServerRepository = $resourceServerRepository;
        $this->authenticationMethodManager = $authenticationMethodManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $resourceServerId = $this->authenticationMethodManager->findResourceServerIdAndCredentials($request, $authentication_method, $resourceServer_credentials);
            if (null !== $resourceServerId) {
                $resourceServer = $this->resourceServerRepository->find($resourceServerId);
                $this->checkResourceServer($resourceServer);
                $this->checkAuthenticationMethod($request, $resourceServer, $authentication_method, $resourceServer_credentials);
                $request = $request->withAttribute('resource_server', $resourceServer);
                $request = $request->withAttribute('resource_server_authentication_method', $authentication_method);
                $request = $request->withAttribute('resource_server_credentials', $resourceServer_credentials);
            }
        } catch (\Exception $e) {
            throw new OAuth2Exception(401, OAuth2Exception::ERROR_INVALID_RESOURCE_SERVER, $e->getMessage(), $e);
        }

        return $handler->handle($request);
    }

    /**
     * @param null|ResourceServer $resourceServer
     */
    private function checkResourceServer(?ResourceServer $resourceServer)
    {
        if (null === $resourceServer) {
            throw new \InvalidArgumentException('Unknown resource server or resource server not authenticated.');
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResourceServer         $resourceServer
     * @param AuthenticationMethod   $authenticationMethod
     * @param mixed                  $resourceServer_credentials
     */
    private function checkAuthenticationMethod(ServerRequestInterface $request, ResourceServer $resourceServer, AuthenticationMethod $authenticationMethod, $resourceServer_credentials)
    {
        if (!in_array($resourceServer->getAuthenticationMethod(), $authenticationMethod->getSupportedMethods())) {
            throw new \InvalidArgumentException('Unknown resource server or resource server not authenticated.');
        }
        if (!$authenticationMethod->isResourceServerAuthenticated($resourceServer, $resourceServer_credentials, $request)) {
            throw new \InvalidArgumentException('Unknown resource server or resource server not authenticated.');
        }
    }
}
