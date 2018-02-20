<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Client\AuthenticationMethod;

use Assert\Assertion;
use OAuth2Framework\Component\Client\Client\OAuth2ClientInterface;

abstract class AbstractAuthenticationMethod implements TokenEndpointAuthenticationMethodInterface
{
    /**
     * @param \OAuth2Framework\Component\Client\Client\OAuth2ClientInterface $client
     */
    protected function checkClientTokenEndpointAuthenticationMethod(OAuth2ClientInterface $client)
    {
        Assertion::keyExists($client->getConfiguration(), 'client_id');
        Assertion::keyExists($client->getConfiguration(), 'token_endpoint_auth_method');
        Assertion::eq($this->getName(), $client->getConfiguration()['token_endpoint_auth_method']);
    }
}
