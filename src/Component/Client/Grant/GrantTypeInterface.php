<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Client\Grant;

use OAuth2Framework\Component\Client\Client\OAuth2ClientInterface;
use OAuth2Framework\Component\Client\ResponseMode\ResponseModeInterface;

interface GrantTypeInterface
{
    /**
     * @param \OAuth2Framework\Component\Client\Client\OAuth2ClientInterface       $client
     * @param \OAuth2Framework\Component\Client\ResponseMode\ResponseModeInterface $response_mode
     */
    public function process(OAuth2ClientInterface $client, ResponseModeInterface $response_mode);
}
