<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\TokenIntrospectionEndpointAuthMethod\TokenIntrospectionEndpointAuthMethodManager;
use Psr\Http\Message\ServerRequestInterface;

final class ResourceServerAuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @var ResourceServerRepositoryInterface
     */
    private $resourceServerRepository;

    /**
     * @var TokenIntrospectionEndpointAuthMethodManager
     */
    private $tokenIntrospectionEndpointAuthMethodManager;

    /**
     * ResourceServerAuthenticationMiddleware constructor.
     *
     * @param ResourceServerRepositoryInterface           $resourceServerRepository
     * @param TokenIntrospectionEndpointAuthMethodManager $tokenIntrospectionEndpointAuthMethodManager
     */
    public function __construct(ResourceServerRepositoryInterface $resourceServerRepository, TokenIntrospectionEndpointAuthMethodManager $tokenIntrospectionEndpointAuthMethodManager)
    {
        $this->resourceServerRepository = $resourceServerRepository;
        $this->tokenIntrospectionEndpointAuthMethodManager = $tokenIntrospectionEndpointAuthMethodManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $resourceServerId = $this->tokenIntrospectionEndpointAuthMethodManager->findResourceServerInformationInTheRequest($request, $authenticationMethod, $resourceServerCredentials);
        if (null === $resourceServerId) {
            throw new OAuth2Exception(401, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_RESOURCE_SERVER, 'error_description' => 'Resource Server authentication failed.']);
        }
        $resourceServer = $this->resourceServerRepository->find($resourceServerId);

        if (null === $resourceServer || false === $this->tokenIntrospectionEndpointAuthMethodManager->isResourceServerAuthenticated($request, $resourceServer, $authenticationMethod, $resourceServerCredentials)) {
            throw new OAuth2Exception(401, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_RESOURCE_SERVER, 'error_description' => 'Resource Server authentication failed.']);
        }

        $request = $request->withAttribute('resource_server', $resourceServer);

        return $delegate->process($request);
    }
}
