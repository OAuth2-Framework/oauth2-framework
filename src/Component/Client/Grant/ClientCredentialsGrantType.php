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

final class ClientCredentialsGrantType implements GrantTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(OAuth2ClientInterface $client, ResponseModeInterface $response_mode)
    {
        return array_merge(
            ['grant_type' => 'client_credentials'],
            $additional_parameters
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPostRequestParameters(array $additional_parameters)
    {
        return array_merge(
            ['grant_type' => 'client_credentials'],
            $additional_parameters
        );
    }
}
