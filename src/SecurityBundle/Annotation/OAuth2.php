<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class OAuth2
{
    public function __construct(
        private ?string $scope = null,
        private ?string $token_type = null,
        private ?string $client_id = null,
        private ?string $resource_owner_id = null,
        private ?array $custom = null
    ) {
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function getResourceOwnerId(): ?string
    {
        return $this->resource_owner_id;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function getTokenType(): ?string
    {
        return $this->token_type;
    }

    public function getCustom(): ?array
    {
        return $this->custom;
    }
}
