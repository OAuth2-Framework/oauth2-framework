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
use OAuth2Framework\Component\Core\Message\OAuth2Message;
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

    /**
     * @param AuthenticationMethod $method
     *
     * @return AuthenticationMethodManager
     */
    public function add(AuthenticationMethod $method): self
    {
        $class = \get_class($method);
        $this->methods[$class] = $method;
        foreach ($method->getSupportedMethods() as $name) {
            $this->names[$name] = $class;
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function list(): array
    {
        return \array_keys($this->names);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->names);
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return AuthenticationMethod
     */
    public function get(string $name): AuthenticationMethod
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(\sprintf('The resource server authentication method "%s" is not supported. Please use one of the following values: %s', $name, \implode(', ', $this->list())));
        }
        $class = $this->names[$name];

        return $this->methods[$class];
    }

    /**
     * @return AuthenticationMethod[]
     */
    public function all(): array
    {
        return \array_values($this->methods);
    }

    /**
     * @param ServerRequestInterface $request
     * @param AuthenticationMethod   $authenticationMethod
     * @param mixed                  $resourceServerCredentials The  resource server credentials found in the request
     *
     * @throws OAuth2Message
     *
     * @return null|ResourceServerId
     */
    public function findResourceServerIdAndCredentials(ServerRequestInterface $request, AuthenticationMethod &$authenticationMethod = null, &$resourceServerCredentials = null)
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

            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_REQUEST, 'Only one authentication method may be used to authenticate the resource server.');
        }

        return $resourceServerId;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResourceServer         $resourceServer
     * @param AuthenticationMethod   $authenticationMethod
     * @param mixed                  $resourceServerCredentials
     *
     * @return bool
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
            $schemes = \array_merge(
                $schemes,
                $method->getSchemesParameters()
            );
        }

        return $schemes;
    }
}
