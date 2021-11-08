<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuer as TrustedIssuerInterface;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuerRepository as TrustedIssuerRepositoryInterface;
use OAuth2Framework\Tests\TestBundle\Entity\TrustedIssuer;

final class TrustedIssuerRepository implements TrustedIssuerRepositoryInterface
{
    /**
     * @var array<string, TrustedIssuer>
     */
    private array $trustedIssuers = [];

    public function save(TrustedIssuerInterface $trustedIssuer): void
    {
        $this->trustedIssuers[$trustedIssuer->name()] = $trustedIssuer;
    }

    public function find(string $trustedIssuer): ?TrustedIssuerInterface
    {
        return $this->trustedIssuers[$trustedIssuer] ?? null;
    }
}
