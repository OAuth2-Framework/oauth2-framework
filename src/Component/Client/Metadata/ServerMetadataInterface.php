<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Client\Metadata;

interface ServerMetadataInterface
{
    /**
     * @param string $server_uri
     * @param bool   $allow_unsecured_connection
     *
     * @throws \InvalidArgumentException
     *
     * @return \OAuth2Framework\Component\Client\Metadata\ServerMetadataInterface
     */
    public static function createFromServerUri($server_uri, $allow_unsecured_connection = false);

    /**
     * @param array $values
     *
     * @throws \InvalidArgumentException
     *
     * @return \OAuth2Framework\Component\Client\Metadata\ServerMetadataInterface
     */
    public static function createFromValues(array $values);

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
