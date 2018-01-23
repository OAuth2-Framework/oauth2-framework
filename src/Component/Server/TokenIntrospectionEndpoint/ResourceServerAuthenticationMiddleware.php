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

namespace OAuth2Framework\Component\Server\TokenIntrospectionEndpoint;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerRepository;
use OAuth2Framework\Component\Server\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ResourceServerAuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @var ResourceServerRepository
     */
    private $resourceServerRepository;

    /**
     * @var TokenIntrospectionEndpointAuthenticationMethodManager
     */
    private $tokenIntrospectionEndpointAuthenticationMethodManager;

    /**
     * ResourceServerAuthenticationMiddleware constructor.
     *
     * @param ResourceServerRepository                              $resourceServerRepository
     * @param TokenIntrospectionEndpointAuthenticationMethodManager $tokenIntrospectionEndpointAuthenticationMethodManager
     */
    public function __construct(ResourceServerRepository $resourceServerRepository, TokenIntrospectionEndpointAuthenticationMethodManager $tokenIntrospectionEndpointAuthenticationMethodManager)
    {
        $this->resourceServerRepository = $resourceServerRepository;
        $this->tokenIntrospectionEndpointAuthenticationMethodManager = $tokenIntrospectionEndpointAuthenticationMethodManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $resourceServerId = $this->tokenIntrospectionEndpointAuthenticationMethodManager->findResourceServerInformationInTheRequest($request, $authenticationMethod, $resourceServerCredentials);
        if (null === $resourceServerId) {
            throw new OAuth2Exception(401, OAuth2Exception::ERROR_INVALID_RESOURCE_SERVER, 'Resource Server authentication failed.');
        }
        $resourceServer = $this->resourceServerRepository->find($resourceServerId);

        if (null === $resourceServer || false === $this->tokenIntrospectionEndpointAuthenticationMethodManager->isResourceServerAuthenticated($request, $resourceServer, $authenticationMethod, $resourceServerCredentials)) {
            throw new OAuth2Exception(401, OAuth2Exception::ERROR_INVALID_RESOURCE_SERVER, 'Resource Server authentication failed.');
        }

        $request = $request->withAttribute('resource_server', $resourceServer);

        return $handler->handle($request);
    }
}
