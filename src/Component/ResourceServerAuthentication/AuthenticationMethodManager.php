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

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticationMethodManager
{
    /**
     * @var AuthenticationMethod[]
     */
    private $methods = [];

    /**
     * @var string[]
     */
    private $names = [];

    public function add(AuthenticationMethod $method): void
    {
        $class = \get_class($method);
        $this->methods[$class] = $method;
        foreach ($method->getSupportedMethods() as $name) {
            $this->names[$name] = $class;
        }
    }

    /**
     * @return string[]
     */
    public function list(): array
    {
        return array_keys($this->names);
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->names);
    }

    public function get(string $name): AuthenticationMethod
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(\Safe\sprintf('The resource server authentication method "%s" is not supported. Please use one of the following values: %s', $name, implode(', ', $this->list())));
        }
        $class = $this->names[$name];

        return $this->methods[$class];
    }

    /**
     * @return AuthenticationMethod[]
     */
    public function all(): array
    {
        return array_values($this->methods);
    }

    /**
     * @param mixed $resourceServerCredentials The resource server credentials found in the request
     */
    public function findResourceServerIdAndCredentials(ServerRequestInterface $request, AuthenticationMethod &$authenticationMethod = null, &$resourceServerCredentials = null): ?ResourceServerId
    {
        $resourceServerId = null;
        $resourceServerCredentials = null;
        foreach ($this->methods as $method) {
            $tempResourceServerId = $method->findResourceServerIdAndCredentials($request, $resourceServerCredentials);
            if (null === $tempResourceServerId) {
                continue;
            }
            if (null === $resourceServerId) {
                $resourceServerId = $tempResourceServerId;
                $authenticationMethod = $method;

                continue;
            }

            throw OAuth2Error::invalidRequest('Only one authentication method may be used to authenticate the resource server.');
        }

        return $resourceServerId;
    }

    /**
     * @param mixed $resourceServerCredentials
     */
    public function isResourceServerAuthenticated(ServerRequestInterface $request, ResourceServer $resourceServer, AuthenticationMethod $authenticationMethod, $resourceServerCredentials): bool
    {
        if (\in_array($resourceServer->getAuthenticationMethod(), $authenticationMethod->getSupportedMethods(), true)) {
            return $authenticationMethod->isResourceServerAuthenticated($resourceServer, $resourceServerCredentials, $request);
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getSchemesParameters(): array
    {
        $schemes = [];
        foreach ($this->all() as $method) {
            $schemes = array_merge(
                $schemes,
                $method->getSchemesParameters()
            );
        }

        return $schemes;
    }
}
