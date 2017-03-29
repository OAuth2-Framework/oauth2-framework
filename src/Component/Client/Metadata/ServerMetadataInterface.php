<?php

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
    static public function createFromServerUri($server_uri, $allow_unsecured_connection = false);
    
    /**
     * @param array $values
     * 
     * @throws \InvalidArgumentException
     *
     * @return \OAuth2Framework\Component\Client\Metadata\ServerMetadataInterface
     */
    static public function createFromValues(array $values);

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
