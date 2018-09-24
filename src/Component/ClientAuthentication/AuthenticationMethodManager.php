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

namespace OAuth2Framework\Component\ClientAuthentication;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
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
        return \array_keys($this->names);
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->names);
    }

    public function get(string $name): AuthenticationMethod
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(\sprintf('The token endpoint authentication method "%s" is not supported. Please use one of the following values: %s', $name, \implode(', ', $this->list())));
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
     * @param mixed $clientCredentials The client credentials found in the request
     */
    public function findClientIdAndCredentials(ServerRequestInterface $request, AuthenticationMethod &$authenticationMethod = null, &$clientCredentials = null): ?ClientId
    {
        $clientId = null;
        $clientCredentials = null;
        foreach ($this->methods as $method) {
            $tempClientId = $method->findClientIdAndCredentials($request, $clientCredentials);
            if (null === $tempClientId) {
                continue;
            }
            if (null === $clientId) {
                $clientId = $tempClientId;
                $authenticationMethod = $method;

                continue;
            }
            if (!$method instanceof None && !$authenticationMethod instanceof None) {
                throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, 'Only one authentication method may be used to authenticate the client.');
            }
            if (!$method instanceof None) {
                $authenticationMethod = $method;
            }
        }

        return $clientId;
    }

    public function isClientAuthenticated(ServerRequestInterface $request, Client $client, AuthenticationMethod $authenticationMethod, $clientCredentials): bool
    {
        if (\in_array($client->get('token_endpoint_auth_method'), $authenticationMethod->getSupportedMethods(), true)) {
            if (false === $client->areClientCredentialsExpired()) {
                return $authenticationMethod->isClientAuthenticated($client, $clientCredentials, $request);
            }
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

    public function getSchemes(array $additionalAuthenticationParameters = []): array
    {
        $schemes = [];
        foreach ($this->all() as $method) {
            $schemes = \array_merge(
                $schemes,
                $method->getSchemesParameters()
            );
        }
        foreach ($schemes as $k => $scheme) {
            $schemes[$k] = $this->appendParameters($scheme, $additionalAuthenticationParameters);
        }

        return $schemes;
    }

    private function appendParameters(string $scheme, array $parameters): string
    {
        $position = \mb_strpos($scheme, ' ', 0, 'utf-8');
        $add_comma = false === $position ? false : true;

        foreach ($parameters as $key => $value) {
            $value = \is_string($value) ? \sprintf('"%s"', $value) : $value;
            if (false === $add_comma) {
                $add_comma = true;
                $scheme = \sprintf('%s %s=%s', $scheme, $key, $value);
            } else {
                $scheme = \sprintf('%s,%s=%s', $scheme, $key, $value);
            }
        }

        return $scheme;
    }
}
