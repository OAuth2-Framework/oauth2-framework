<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccount as UserAccountInterface;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

final class UserAccount implements UserAccountInterface, SymfonyUserInterface, EquatableInterface
{
    private $username;

    /**
     * @var string[]
     */
    private $roles;

    private $lastLoginAt;

    private $lastUpdateAt;

    private $userAccountId;

    private $data = [];

    public function __construct(UserAccountId $userAccountId, string $username, array $roles, ?\DateTimeImmutable $lastLoginAt, ?\DateTimeImmutable $lastUpdateAt, array $data)
    {
        $this->userAccountId = $userAccountId;
        $this->username = $username;
        $this->roles = $roles;
        $this->lastLoginAt = $lastLoginAt;
        $this->lastUpdateAt = $lastUpdateAt;
        $this->data = $data;
    }

    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    public function getPublicId(): ResourceOwnerId
    {
        return $this->userAccountId;
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->data);
    }

    public function get(string $key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(\Safe\sprintf('The user account parameter "%s" does not exist.', $key));
        }

        return $this->data[$key];
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
