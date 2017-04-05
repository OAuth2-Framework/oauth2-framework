<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Client\Request;

interface OAuth2RequestInterface
{
    /**
     * If this method is called, requests using the https scheme without a valid certificates are allowed.
     * We do not recommend you to call this method.
     * @return void
     */
    public function allowUnsecuredRequests();

    /**
     * If this method is called, requests using the https scheme without a valid certificates are not allowed (default behaviour).
     * @return void
     */
    public function disallowUnsecuredRequests();

    /**
     * If true, unsecured requests are allowed (not recommended).
     *
     * @return bool
     */
    public function areUnsecuredRequestsAllowed();

    /**
     * @param string $proxy
     * @return void
     */
    public function setProxy($proxy);

    /**
     * Returns the proxy settings.
     *
     * @return null|string
     */
    public function getProxy();

    /**
     * Unset the proxy settings.
     * @return void
     */
    public function unsetProxy();

    /**
     * This method will send a request against the resource server.
     * The.
     *
     * @param string $method
     * @param string $resource
     *
     * @return \OAuth2Framework\Component\Client\Response\OAuth2ResponseInterface
     */
    public function call($method, $resource);

    /**
     * @param string $resource
     *
     * @return \OAuth2Framework\Component\Client\Response\OAuth2ResponseInterface
     */
    public function get($resource);

    /**
     * @param string $resource
     *
     * @return \OAuth2Framework\Component\Client\Response\OAuth2ResponseInterface
     */
    public function post($resource);

    /**
     * @param string $resource
     *
     * @return \OAuth2Framework\Component\Client\Response\OAuth2ResponseInterface
     */
    public function put($resource);

    /**
     * @param string $resource
     *
     * @return \OAuth2Framework\Component\Client\Response\OAuth2ResponseInterface
     */
    public function patch($resource);

    /**
     * @param string $resource
     *
     * @return \OAuth2Framework\Component\Client\Response\OAuth2ResponseInterface
     */
    public function delete($resource);
}
