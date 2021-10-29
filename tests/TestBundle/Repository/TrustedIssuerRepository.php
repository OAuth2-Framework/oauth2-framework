<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuer;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuerRepository as TrustedIssuerRepositoryInterface;

final class TrustedIssuerRepository implements TrustedIssuerRepositoryInterface
{
    public function find(string $trustedIssuer): ?TrustedIssuer
    {
        return null;
    }
}
