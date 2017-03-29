<?php

namespace OAuth2Framework\Component\Client\Grant;

use Assert\Assertion;

final class AuthorizationCodeGrantType implements GrantTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPostRequestParameters(array $additional_parameters)
    {
        Assertion::keyExists($additional_parameters, 'code');
        return array_merge(
            ['grant_type' => 'authorization_code',],
            $additional_parameters
        );
    }
}
