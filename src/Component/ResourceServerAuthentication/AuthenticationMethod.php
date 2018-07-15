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

use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use Psr\Http\Message\ServerRequestInterface;

interface AuthenticationMethod
{
    /**
     * @return string[]
     */
    public function getSupportedMethods(): array;

    /**
     * Find a ResourceServer using the request.
     * If the ResourceServer is confidential, the ResourceServer credentials must be checked.
     *
     * @param ServerRequestInterface $request                   The request
     * @param mixed                  $resourceServerCredentials The resource server credentials found in the request
     *
     * @return null|ResourceServerId Return the  resource server public ID if found else null. If credentials have are needed to authenticate the ResourceServer, they are set to the variable $resourceServerCredentials
     */
    public function findResourceServerIdAndCredentials(ServerRequestInterface $request, &$resourceServerCredentials = null): ?ResourceServerId;

    /**
     * This method verifies the ResourceServer credentials in the request.
     *
     * @param ResourceServer         $resourceServer
     * @param mixed                  $resourceServerCredentials
     * @param ServerRequestInterface $request
     *
     * @return bool Returns true if the  resource server is authenticated, else false
     */
    public function isResourceServerAuthenticated(ResourceServer $resourceServer, $resourceServerCredentials, ServerRequestInterface $request): bool;

    /**
     * @return string[]
     */
    public function getSchemesParameters(): array;
}
