<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\TrustedIssuer;

interface TrustedIssuerRepository
{
    public function find(string $trustedIssuer): ?TrustedIssuer;
}
