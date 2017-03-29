<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Tests\TestBundle\Entity;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class User implements UserInterface, UserAccountInterface
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
     * @var array
     */
    private $parameters = [];

    /**
     * @param string $username
     * @param string $password
     * @param string|null $salt
     * @param string[] $roles
     * @param string[] $oauth2Passwords
     * @param UserAccountId $publicId
     * @param \DateTimeImmutable|null $lastLoginAt
     * @param array $parameters
     */
    public function __construct(string $username, string $password, string $salt = null, array $roles, array $oauth2Passwords = [], UserAccountId $publicId, \DateTimeImmutable $lastLoginAt = null,array $parameters = [])
    {
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->roles = $roles;
        $this->oauth2Passwords = $oauth2Passwords;
        $this->publicId = $publicId;
        $this->lastLoginAt = $lastLoginAt;
        $this->parameters = $parameters;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string|null $salt
     * @param string[] $roles
     * @param string[] $oauth2Passwords
     * @param UserAccountId $publicId
     * @param \DateTimeImmutable|null $lastLoginAt
     * @param array $parameters
     *
     * @return User
     */
    public static function create(string $username, string $password, string $salt = null, array $roles, array $oauth2Passwords = [], UserAccountId $publicId, \DateTimeImmutable $lastLoginAt = null,array $parameters = [])
    {
        return new self($username, $password, $salt, $roles, $oauth2Passwords, $publicId, $lastLoginAt,$parameters);
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

    /**
     * @return \DateTimeImmutable|null
     */
    public function getLastLoginAt()
    {
        return $this->lastLoginAt;
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
        Assertion::true($this->has($key), sprintf('Configuration value with key \'%s\' does not exist.', $key));

        return $this->parameters[$key];
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return null|string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function eraseCredentials()
    {
    }

    /**
     * @param UserInterface $user
     *
     * @return bool
     */
    public function equals(UserInterface $user)
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
