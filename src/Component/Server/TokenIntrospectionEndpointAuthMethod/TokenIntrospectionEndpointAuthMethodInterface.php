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
use Psr\Http\Message\ServerRequestInterface;

interface TokenIntrospectionEndpointAuthMethodInterface
{
    /**
     * Find a Resource Server using the request.
     *
     * @param ServerRequestInterface $request                   The request
     * @param mixed                  $resourceServerCredentials The Resource Server credentials found in the request
     *
     * @return null|ResourceServerId Return the Resource Server public ID if found else null. If credentials have are needed to authenticate the Resource Server, they are set to the variable $resourceServerCredentials
     */
    public function findResourceServerId(ServerRequestInterface $request, &$resourceServerCredentials = null): ?ResourceServerId;

    /**
     * This method verifies the Resource Server credentials in the request.
     *
     * @param ResourceServerInterface $resourceServer
     * @param mixed                   $resourceServerCredentials
     * @param ServerRequestInterface  $request
     *
     * @return bool Returns true if the Resource Server is authenticated, else false
     */
    public function isResourceServerAuthenticated(ResourceServerInterface $resourceServer, $resourceServerCredentials, ServerRequestInterface $request): bool;
}
