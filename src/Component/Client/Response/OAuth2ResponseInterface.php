<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Client\Response;

use Psr\Http\Message\ResponseInterface;

interface OAuth2ResponseInterface
{
    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \OAuth2Framework\Component\Client\Response\OAuth2ResponseInterface
     */
    public static function createFromResponse(ResponseInterface $response);

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
}
