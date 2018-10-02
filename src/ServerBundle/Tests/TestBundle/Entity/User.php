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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\User\User as OAuth2UserInterface;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

class User implements SymfonyUserInterface, OAuth2UserInterface, EquatableInterface
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string[]
     */
    private $roles;

    /**
     * @var \DateTimeImmutable|null
     */
    private $lastLoginAt;

    /**
     * @var \DateTimeImmutable|null
     */
    private $lastUpdateAt;

    /**
     * @var string[]
     */
    private $user_account_ids;

    /**
     * @param string[] $roles
     * @param string[] $user_account_ids
     */
    public function __construct(string $username, array $roles, array $user_account_ids, ?\DateTimeImmutable $lastLoginAt, ?\DateTimeImmutable $lastUpdateAt)
    {
        $this->username = $username;
        $this->roles = $roles;
        $this->lastLoginAt = $lastLoginAt;
        $this->lastUpdateAt = $lastUpdateAt;
        $this->user_account_ids = $user_account_ids;
    }

    public function getLastLoginAt(): ?int
    {
        return $this->lastLoginAt ? $this->lastLoginAt->getTimestamp() : null;
    }

    public function getLastUpdateAt(): ?int
    {
        return $this->lastUpdateAt ? $this->lastUpdateAt->getTimestamp() : null;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getSalt()
    {
        return;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return '';
    }

    public function eraseCredentials()
    {
    }

    public function getAccountIds(UserAccountId $userAccountId): array
    {
        return $this->user_account_ids;
    }

    public function hasAccountId(UserAccountId $userAccountId): bool
    {
        return \in_array($userAccountId->getValue(), $this->user_account_ids, true);
    }

    public function isEqualTo(SymfonyUserInterface $user)
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}
