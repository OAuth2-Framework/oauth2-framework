<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRegistrationEndpoint;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

interface InitialAccessToken
{
    public function getId(): InitialAccessTokenId;

    public function getUserAccountId(): ?UserAccountId;

    public function getExpiresAt(): ?DateTimeImmutable;

    public function hasExpired(): bool;

    public function isRevoked(): bool;

    /**
     * This method should be immutable.
     */
    public function markAsRevoked(): static;
}
