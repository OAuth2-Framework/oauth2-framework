<?php

namespace OAuth2Framework\Component\Client\Grant;

use OAuth2Framework\Component\Client\Client\OAuth2ClientInterface;
use OAuth2Framework\Component\Client\ResponseMode\ResponseModeInterface;

final class ClientCredentialsGrantType implements GrantTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(OAuth2ClientInterface $client, ResponseModeInterface $response_mode)
    {
        return array_merge(
            ['grant_type' => 'client_credentials',],
            $additional_parameters
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPostRequestParameters(array $additional_parameters)
    {
        return array_merge(
            ['grant_type' => 'client_credentials',],
            $additional_parameters
        );
    }
}
