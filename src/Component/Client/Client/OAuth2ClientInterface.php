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

use OAuth2Framework\Component\Client\Grant\GrantTypeInterface;
use OAuth2Framework\Component\Client\ResponseMode\ResponseModeInterface;

/**
 * @method string getPublicId()
 * @method string getClientSecret()
 */
interface OAuth2ClientInterface extends \JsonSerializable
{
    /**
     * The configuration of the client.
     *
     * @return array
     */
    public function getConfiguration();

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function set($key, $value);

    /**
     * This method will try to get an access token.
     *
     * @param \OAuth2Framework\Component\Client\Grant\GrantTypeInterface           $grant
     * @param \OAuth2Framework\Component\Client\ResponseMode\ResponseModeInterface $response_mode
     * @param string[]                                                             $scope
     *
     * @return \OAuth2Framework\Component\Client\Response\OAuth2ResponseInterface
     */
    public function getAccessToken(GrantTypeInterface $grant, ResponseModeInterface $response_mode, array $scope = []);
}
