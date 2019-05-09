<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\ClientRegistrationEndpoint;

use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

abstract class AbstractInitialAccessToken implements InitialAccessToken
{
    /**
     * @var bool
     */
    private $revoked;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expiresAt;

    /**
     * @var UserAccountId|null
     */
    private $userAccountId;

    public function __construct(?UserAccountId $userAccountId, ?\DateTimeImmutable $expiresAt)
    {
        $this->expiresAt = $expiresAt;
        $this->userAccountId = $userAccountId;
        $this->revoked = false;
    }

    public function getUserAccountId(): ?UserAccountId
    {
        return $this->userAccountId;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function hasExpired(): bool
    {
        return null !== $this->expiresAt ? $this->expiresAt->getTimestamp() < \time() : false;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function markAsRevoked(): void
    {
        $this->revoked = true;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'initial_access_token_id' => $this->getId()->getValue(),
            'user_account_id' => null !== $this->getUserAccountId() ? $this->getUserAccountId()->getValue() : null,
            'expires_at' => null !== $this->getExpiresAt() ? $this->getExpiresAt()->getTimestamp() : null,
            'is_revoked' => $this->isRevoked(),
        ];

        return $data;
    }
}
