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

namespace OAuth2Framework\Component\Server\TokenEndpoint\AuthenticationMethod;

use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

final class TokenEndpointAuthenticationMethodManager
{
    /**
     * @var TokenEndpointAuthenticationMethod[]
     */
    private $tokenEndpointAuthenticationMethodNames = [];

    /**
     * @var TokenEndpointAuthenticationMethod[]
     */
    private $tokenEndpointAuthenticationMethods = [];

    /**
     * @param TokenEndpointAuthenticationMethod $tokenEndpointAuthenticationMethod
     *
     * @return TokenEndpointAuthenticationMethodManager
     */
    public function add(TokenEndpointAuthenticationMethod $tokenEndpointAuthenticationMethod): self
    {
        $this->tokenEndpointAuthenticationMethods[] = $tokenEndpointAuthenticationMethod;
        foreach ($tokenEndpointAuthenticationMethod->getSupportedAuthenticationMethods() as $method_name) {
            $this->tokenEndpointAuthenticationMethodNames[$method_name] = $tokenEndpointAuthenticationMethod;
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        return array_keys($this->tokenEndpointAuthenticationMethodNames);
    }

    /**
     * @param string $tokenEndpointAuthenticationMethod
     *
     * @return bool
     */
    public function has(string $tokenEndpointAuthenticationMethod): bool
    {
        return array_key_exists($tokenEndpointAuthenticationMethod, $this->tokenEndpointAuthenticationMethodNames);
    }

    /**
     * @param string $tokenEndpointAuthenticationMethod
     *
     * @throws \InvalidArgumentException
     *
     * @return TokenEndpointAuthenticationMethod
     */
    public function get(string $tokenEndpointAuthenticationMethod): TokenEndpointAuthenticationMethod
    {
        Assertion::true($this->has($tokenEndpointAuthenticationMethod), sprintf('The token endpoint authentication method "%s" is not supported. Please use one of the following values: %s', $tokenEndpointAuthenticationMethod, implode(', ', $this->all())));

        return $this->tokenEndpointAuthenticationMethodNames[$tokenEndpointAuthenticationMethod];
    }

    /**
     * @return TokenEndpointAuthenticationMethod[]
     */
    public function getTokenEndpointAuthenticationMethods(): array
    {
        return array_values($this->tokenEndpointAuthenticationMethods);
    }

    /**
     * @param ServerRequestInterface            $request
     * @param TokenEndpointAuthenticationMethod $authenticationMethod
     * @param mixed                             $clientCredentials    The client credentials found in the request
     *
     * @throws OAuth2Exception
     *
     * @return null|ClientId
     */
    public function findClientInformationInTheRequest(ServerRequestInterface $request, TokenEndpointAuthenticationMethod &$authenticationMethod = null, &$clientCredentials = null)
    {
        $clientId = null;
        $clientCredentials = null;
        foreach ($this->getTokenEndpointAuthenticationMethods() as $method) {
            $temp = $method->findClientId($request, $clientCredentials);
            if (null !== $temp) {
                if (null !== $clientId) {
                    if (!$method instanceof None && !$authenticationMethod instanceof None) {
                        $authenticationMethod = null;

                        throw new OAuth2Exception(
                            400, ['error' => OAuth2Exception::ERROR_INVALID_REQUEST, 'error_description' => 'Only one authentication method may be used to authenticate the client.']);
                    } else {
                        if (!$method instanceof None) {
                            $authenticationMethod = $method;
                        }
                    }
                } else {
                    $clientId = $temp;
                    $authenticationMethod = $method;
                }
            }
        }

        return $clientId;
    }

    /**
     * @param ServerRequestInterface            $request
     * @param Client                            $client
     * @param TokenEndpointAuthenticationMethod $authenticationMethod
     * @param mixed                             $clientCredentials
     *
     * @return bool
     */
    public function isClientAuthenticated(ServerRequestInterface $request, Client $client, TokenEndpointAuthenticationMethod $authenticationMethod, $clientCredentials): bool
    {
        if (true === $client->isDeleted()) {
            return false;
        }
        if (in_array($client->get('token_endpoint_auth_method'), $authenticationMethod->getSupportedAuthenticationMethods())) {
            if (false === $client->areClientCredentialsExpired()) {
                return $authenticationMethod->isClientAuthenticated($client, $clientCredentials, $request);
            }
        }

        return false;
    }
}
