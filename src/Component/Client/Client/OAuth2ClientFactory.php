<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Client\Client;

use Assert\Assertion;
use OAuth2Framework\Component\Client\AuthenticationMethod\ClientSecretBasicTokenEndpointAuthenticationMethod;
use OAuth2Framework\Component\Client\AuthenticationMethod\ClientSecretJwtTokenEndpointAuthenticationMethod;
use OAuth2Framework\Component\Client\AuthenticationMethod\ClientSecretPostTokenEndpointAuthenticationMethod;
use OAuth2Framework\Component\Client\AuthenticationMethod\NoneTokenEndpointAuthenticationMethod;
use OAuth2Framework\Component\Client\AuthenticationMethod\PrivateKeyJwtTokenEndpointAuthenticationMethod;

class OAuth2ClientFactory
{
    /**
     * @param array $values
     *
     * @return \OAuth2Framework\Component\Client\Client\OAuth2ClientInterface
     */
    public static function createFromValues(array $values)
    {
        Assertion::keyExists($values, 'public_id');
        Assertion::keyExists($values, 'token_endpoint_auth_method');
        $authentication_method = self::getAuthenticationMethod($values['token_endpoint_auth_method']);

        return new OAuth2Client($authentication_method, $values);
    }

    /**
     * @param string $method
     *
     * @return \OAuth2Framework\Component\Client\AuthenticationMethod\TokenEndpointAuthenticationMethodInterface
     */
    private static function getAuthenticationMethod($method)
    {
        $methods = self::getAuthenticationMethods();
        Assertion::keyExists($methods, $method);
        $class = $methods[$method];

        return new $class();
    }

    /**
     * @return \OAuth2Framework\Component\Client\AuthenticationMethod\TokenEndpointAuthenticationMethodInterface[]
     */
    protected static function getAuthenticationMethods()
    {
        return [
            'client_secret_basic' => ClientSecretBasicTokenEndpointAuthenticationMethod::class,
            'client_secret_post'  => ClientSecretPostTokenEndpointAuthenticationMethod::class,
            'client_secret_jwt'   => ClientSecretJwtTokenEndpointAuthenticationMethod::class,
            'none'                => NoneTokenEndpointAuthenticationMethod::class,
            'private_key_jwt'     => PrivateKeyJwtTokenEndpointAuthenticationMethod::class,
        ];
    }
}
