<?php

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
