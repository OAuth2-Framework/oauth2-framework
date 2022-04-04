<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRegistrationEndpoint;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

abstract class AbstractInitialAccessToken implements InitialAccessToken
{
    private bool $revoked;

    public function __construct(
        private readonly ?UserAccountId $userAccountId,
        private readonly ?DateTimeImmutable $expiresAt
    ) {
        $this->revoked = false;
    }

    public function getUserAccountId(): ?UserAccountId
    {
        return $this->userAccountId;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function hasExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->getTimestamp() < time();
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function markAsRevoked(): static
    {
        $clone = clone $this;
        $clone->revoked = true;

        return $clone;
    }
}
