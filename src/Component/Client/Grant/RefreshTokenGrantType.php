<?php

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
            ['grant_type' => 'refresh_token',],
            $additional_parameters
        );
    }
}
