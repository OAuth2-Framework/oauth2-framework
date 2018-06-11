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

use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, UserAccount, EquatableInterface
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var null|string
     */
    private $salt;

    /**
     * @var string[]
     */
    private $roles;

    /**
     * @var string[]
     */
    private $oauth2Passwords = [];

    /**
     * @var UserAccountId
     */
    private $publicId;

    /**
     * @var \DateTimeImmutable|null
     */
    private $lastLoginAt;

    /**
     * @var \DateTimeImmutable|null
     */
    private $lastUpdateAt;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @param string                  $username
     * @param string                  $password
     * @param string|null             $salt
     * @param string[]                $roles
     * @param string[]                $oauth2Passwords
     * @param UserAccountId           $publicId
     * @param \DateTimeImmutable|null $lastLoginAt
     * @param \DateTimeImmutable|null $lastLoginAt
     * @param array                   $parameters
     */
    public function __construct(string $username, string $password, string $salt = null, array $roles, array $oauth2Passwords, UserAccountId $publicId, ?int $lastLoginAt = null, ?int $lastUpdateAt = null, array $parameters = [])
    {
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->roles = $roles;
        $this->oauth2Passwords = $oauth2Passwords;
        $this->publicId = $publicId;
        $this->lastLoginAt = $lastLoginAt;
        $this->lastUpdateAt = $lastUpdateAt;
        $this->parameters = $parameters;
    }

    /**
     * @param string                  $username
     * @param string                  $password
     * @param string|null             $salt
     * @param string[]                $roles
     * @param string[]                $oauth2Passwords
     * @param UserAccountId           $publicId
     * @param \DateTimeImmutable|null $lastLoginAt
     * @param \DateTimeImmutable|null $lastUpdateAt
     * @param array                   $parameters
     *
     * @return User
     */
    public static function create(string $username, string $password, string $salt = null, array $roles, array $oauth2Passwords, UserAccountId $publicId, \DateTimeImmutable $lastLoginAt = null, \DateTimeImmutable $lastUpdateAt = null, array $parameters = [])
    {
        return new self($username, $password, $salt, $roles, $oauth2Passwords, $publicId, $lastLoginAt, $lastUpdateAt, $parameters);
    }

    /**
     * @return array
     */
    public function getOAuth2Passwords(): array
    {
        return $this->oauth2Passwords;
    }

    /**
     * @return ResourceOwnerId
     */
    public function getPublicId(): ResourceOwnerId
    {
        return $this->publicId;
    }

    public function getUserAccountId(): UserAccountId
    {
        $id = $this->getPublicId();
        if (!$id instanceof UserAccountId) {
            throw new \RuntimeException();
        }

        return $id;
    }

    /**
     * @return int|null
     */
    public function getLastLoginAt(): ?int
    {
        return $this->lastLoginAt ? $this->lastLoginAt->getTimestamp() : null;
    }

    /**
     * @return int|null
     */
    public function getLastUpdateAt(): ?int
    {
        return $this->lastUpdateAt ? $this->lastUpdateAt->getTimestamp() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(sprintf('Configuration value with key "%s" does not exist.', $key));
        }

        return $this->parameters[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}
