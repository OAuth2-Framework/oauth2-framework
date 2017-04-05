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

namespace OAuth2Framework\Component\Server\TokenIntrospectionEndpointAuthMethod;

use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class TokenIntrospectionEndpointAuthMethodManager
{
    /**
     * @var TokenIntrospectionEndpointAuthMethodInterface[]
     */
    private $tokenIntrospectionEndpointAuthMethods = [];

    /**
     * @param TokenIntrospectionEndpointAuthMethodInterface $tokenIntrospectionEndpointAuthMethod
     *
     * @return TokenIntrospectionEndpointAuthMethodManager
     */
    public function addTokenIntrospectionEndpointAuthMethod(TokenIntrospectionEndpointAuthMethodInterface $tokenIntrospectionEndpointAuthMethod): TokenIntrospectionEndpointAuthMethodManager
    {
        $this->tokenIntrospectionEndpointAuthMethods[] = $tokenIntrospectionEndpointAuthMethod;

        return $this;
    }

    /**
     * @param ServerRequestInterface                        $request
     * @param TokenIntrospectionEndpointAuthMethodInterface $authenticationMethod
     * @param mixed                                         $resourceServerCredentials The resourceServer credentials found in the request
     *
     * @throws OAuth2Exception
     *
     * @return null|ResourceServerId
     */
    public function findResourceServerInformationInTheRequest(ServerRequestInterface $request, ? TokenIntrospectionEndpointAuthMethodInterface &$authenticationMethod, &$resourceServerCredentials = null): ? ResourceServerId
    {
        $resourceServerId = null;
        $resourceServerCredentials = null;
        foreach ($this->tokenIntrospectionEndpointAuthMethods as $method) {
            $temp = $method->findResourceServerId($request, $resourceServerCredentials);
            if (null !== $temp) {
                if (null !== $resourceServerId) {
                    $authenticationMethod = null;
                    throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => 'Only one authentication method may be used to authenticate the resourceServer.']);
                } else {
                    $resourceServerId = $temp;
                    $authenticationMethod = $method;
                }
            }
        }

        return $resourceServerId;
    }

    /**
     * @param ServerRequestInterface                        $request
     * @param ResourceServerInterface                       $resourceServer
     * @param TokenIntrospectionEndpointAuthMethodInterface $authenticationMethod
     * @param mixed                                         $resourceServerCredentials
     *
     * @return bool
     */
    public function isResourceServerAuthenticated(ServerRequestInterface $request, ResourceServerInterface $resourceServer, TokenIntrospectionEndpointAuthMethodInterface $authenticationMethod, $resourceServerCredentials): bool
    {
        return $authenticationMethod->isResourceServerAuthenticated($resourceServer, $resourceServerCredentials, $request);
    }
}
