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

namespace OAuth2Framework\Component\Server\TokenEndpointAuthMethod;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class TokenEndpointAuthMethodManager
{
    /**
     * @var TokenEndpointAuthMethodInterface[]
     */
    private $tokenEndpointAuthMethodNames = [];

    /**
     * @var TokenEndpointAuthMethodInterface[]
     */
    private $tokenEndpointAuthMethods = [];

    /**
     * @param TokenEndpointAuthMethodInterface $tokenEndpointAuthMethod
     *
     * @return TokenEndpointAuthMethodManager
     */
    public function add(TokenEndpointAuthMethodInterface $tokenEndpointAuthMethod): TokenEndpointAuthMethodManager
    {
        $this->tokenEndpointAuthMethods[] = $tokenEndpointAuthMethod;
        foreach ($tokenEndpointAuthMethod->getSupportedAuthenticationMethods() as $method_name) {
            $this->tokenEndpointAuthMethodNames[$method_name] = $tokenEndpointAuthMethod;
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        return array_keys($this->tokenEndpointAuthMethodNames);
    }

    /**
     * @param string $tokenEndpointAuthMethod
     *
     * @return bool
     */
    public function has(string $tokenEndpointAuthMethod): bool
    {
        return array_key_exists($tokenEndpointAuthMethod, $this->tokenEndpointAuthMethodNames);
    }

    /**
     * @param string $tokenEndpointAuthMethod
     *
     * @throws \InvalidArgumentException
     *
     * @return TokenEndpointAuthMethodInterface
     */
    public function get(string $tokenEndpointAuthMethod): TokenEndpointAuthMethodInterface
    {
        Assertion::true($this->has($tokenEndpointAuthMethod), sprintf('The token endpoint authentication method \'%s\' is not supported. Please use one of the following values: %s', $tokenEndpointAuthMethod, implode(', ', $this->all())));

        return $this->tokenEndpointAuthMethodNames[$tokenEndpointAuthMethod];
    }

    /**
     * @return TokenEndpointAuthMethodInterface[]
     */
    public function getTokenEndpointAuthMethods(): array
    {
        return array_values($this->tokenEndpointAuthMethods);
    }

    /**
     * @param ServerRequestInterface           $request
     * @param TokenEndpointAuthMethodInterface $authenticationMethod
     * @param mixed                            $clientCredentials    The client credentials found in the request
     *
     * @throws OAuth2Exception
     *
     * @return null|ClientId
     */
    public function findClientInformationInTheRequest(ServerRequestInterface $request, TokenEndpointAuthMethodInterface &$authenticationMethod = null, &$clientCredentials = null)
    {
        $clientId = null;
        $clientCredentials = null;
        foreach ($this->getTokenEndpointAuthMethods() as $method) {
            $temp = $method->findClientId($request, $clientCredentials);
            if (null !== $temp) {
                if (null !== $clientId) {
                    $authenticationMethod = null;
                    throw new OAuth2Exception(
                        400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => 'Only one authentication method may be used to authenticate the client.']);
                } else {
                    $clientId = $temp;
                    $authenticationMethod = $method;
                }
            }
        }

        return $clientId;
    }

    /**
     * @param ServerRequestInterface           $request
     * @param Client                           $client
     * @param TokenEndpointAuthMethodInterface $authenticationMethod
     * @param mixed                            $clientCredentials
     *
     * @return bool
     */
    public function isClientAuthenticated(ServerRequestInterface $request, Client $client, TokenEndpointAuthMethodInterface $authenticationMethod, $clientCredentials): bool
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
