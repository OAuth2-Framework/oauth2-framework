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
use OAuth2Framework\Component\Client\AuthenticationMethod\TokenEndpointAuthenticationMethodInterface;
use OAuth2Framework\Component\Client\Behaviour\Metadata;
use OAuth2Framework\Component\Client\Grant\GrantTypeInterface;
use OAuth2Framework\Component\Client\ResponseMode\ResponseModeInterface;

/**
 * @method string getPublicId()
 * @method string getClientSecret()
 * @method \OAuth2Framework\Component\Client\AuthenticationMethod\TokenEndpointAuthenticationMethodInterface getAuthenticationMethod()
 */
class OAuth2Client implements OAuth2ClientInterface
{
    use Metadata;

    /**
     * @var \OAuth2Framework\Component\Client\AuthenticationMethod\TokenEndpointAuthenticationMethodInterface
     */
    private $authentication_method;

    /**
     * @var array
     */
    private $configuration;

    /**
     * OAuth2Client constructor.
     *
     * @param \OAuth2Framework\Component\Client\AuthenticationMethod\TokenEndpointAuthenticationMethodInterface $authentication_method
     * @param array                                                                                             $configuration
     */
    public function __construct(TokenEndpointAuthenticationMethodInterface $authentication_method, array $configuration)
    {
        $this->authentication_method = $authentication_method;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function getValues()
    {
        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function setValue($key, $value)
    {
        Assertion::string($key);
        $this->configuration[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->getConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken(GrantTypeInterface $grant, ResponseModeInterface $response_mode, array $scope = [])
    {
        $grant->process();
        // TODO: Implement getAccessToken() method.
    }
}
