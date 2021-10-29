<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\TrustedIssuer;

use Jose\Component\Core\JWKSet;

interface TrustedIssuer
{
    public function name(): string;

    /**
     * @return string[]
     */
    public function getAllowedAssertionTypes(): array;

    /**
     * @return string[]
     */
    public function getAllowedSignatureAlgorithms(): array;

    public function getJWKSet(): JWKSet;
}
