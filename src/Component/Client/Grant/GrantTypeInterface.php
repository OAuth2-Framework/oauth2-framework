<?php

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
