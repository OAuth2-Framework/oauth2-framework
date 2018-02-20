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

use OAuth2Framework\Component\Client\Client\OAuth2ClientInterface;
use OAuth2Framework\Component\Client\Metadata\ServerMetadata;
use Psr\Http\Message\RequestInterface;

interface TokenEndpointAuthenticationMethodInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param \OAuth2Framework\Component\Client\Metadata\ServerMetadata      $server_metadata
     * @param \OAuth2Framework\Component\Client\Client\OAuth2ClientInterface $client
     * @param \Psr\Http\Message\RequestInterface                             $request
     * @param array                                                          $post_request
     *
     * @return mixed
     */
    public function prepareRequest(ServerMetadata $server_metadata, OAuth2ClientInterface $client, RequestInterface &$request, array &$post_request);
}
