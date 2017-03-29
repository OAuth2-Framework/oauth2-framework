<?php

namespace OAuth2Framework\Component\Client\Grant;

final class JWTBearerGrantType implements GrantTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPostRequestParameters(array $additional_parameters)
    {
        return [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        ];
    }
}
