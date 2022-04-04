<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Entity;

use Jose\Component\Core\JWKSet;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuer as  TrustedIssuerInterface;

final class TrustedIssuer implements TrustedIssuerInterface
{
    public function __construct(
        private readonly string $name,
        private readonly array $allowedAssertionTypes,
        private readonly array $allowedSignatureAlgorithms,
        private readonly JWKSet $jwkset
    ) {
    }

    public static function create(
        string $name,
        array $allowedAssertionTypes,
        array $allowedSignatureAlgorithms,
        JWKSet $jwkset
    ): self {
        return new self($name, $allowedAssertionTypes, $allowedSignatureAlgorithms, $jwkset);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getAllowedAssertionTypes(): array
    {
        return $this->allowedAssertionTypes;
    }

    /**
     * @return string[]
     */
    public function getAllowedSignatureAlgorithms(): array
    {
        return $this->allowedSignatureAlgorithms;
    }

    public function getJWKSet(): JWKSet
    {
        return $this->jwkset;
    }
}
