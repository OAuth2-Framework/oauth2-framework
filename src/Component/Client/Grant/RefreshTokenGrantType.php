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

use Assert\Assertion;

final class RefreshTokenGrantType implements GrantTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPostRequestParameters(array $additional_parameters)
    {
        Assertion::keyExists($additional_parameters, 'refresh_token');

        return array_merge(
            ['grant_type' => 'refresh_token'],
            $additional_parameters
        );
    }
}
