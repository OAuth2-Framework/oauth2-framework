<?php

namespace OAuth2Framework\Component\Client\Grant;

use Assert\Assertion;

final class ResourceOwnerPasswordCredentialsGrantType implements GrantTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPostRequestParameters(array $additional_parameters)
    {
        Assertion::keyExists($additional_parameters, 'username');
        Assertion::keyExists($additional_parameters, 'password');
        return array_merge(
            ['grant_type' => 'password',],
            $additional_parameters
        );
    }
}
