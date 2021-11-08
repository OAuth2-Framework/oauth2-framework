<?php

declare(strict_types=1);

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
     * Find a ResourceServer using the request. If the ResourceServer is confidential, the ResourceServer credentials
     * must be checked.
     *
     * @param ServerRequestInterface $request                   The request
     * @param mixed|null             $resourceServerCredentials The resource server credentials found in the request
     *
     * @return ResourceServerId|null Return the  resource server public ID if found else null. If credentials have are needed to authenticate the ResourceServer, they are set to the variable $resourceServerCredentials
     */
    public function findResourceServerIdAndCredentials(
        ServerRequestInterface $request,
        mixed &$resourceServerCredentials = null
    ): ?ResourceServerId;

    /**
     * This method verifies the ResourceServer credentials in the request.
     *
     * @return bool Returns true if the  resource server is authenticated, else false
     */
    public function isResourceServerAuthenticated(
        ResourceServer $resourceServer,
        mixed $resourceServerCredentials,
        ServerRequestInterface $request
    ): bool;

    /**
     * @return string[]
     */
    public function getSchemesParameters(): array;
}
