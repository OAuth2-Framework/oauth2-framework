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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;

final class UserAccount implements UserAccountInterface
{
    /**
     * @var UserAccountId
     */
    private $id;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var \DateTimeImmutable|null
     */
    private $lastLoginAt;

    /**
     * UserAccount constructor.
     *
     * @param UserAccountId           $id
     * @param \DateTimeImmutable|null $lastLoginAt
     * @param array                   $parameters
     */
    protected function __construct(UserAccountId $id, \DateTimeImmutable $lastLoginAt = null, array $parameters)
    {
        $this->id = $id;
        $this->parameters = $parameters;
        $this->lastLoginAt = $lastLoginAt;
    }

    /**
     * @param UserAccountId           $id
     * @param \DateTimeImmutable|null $lastLoginAt
     * @param array                   $parameters
     *
     * @return UserAccount
     */
    public static function create(UserAccountId $id, \DateTimeImmutable $lastLoginAt = null, array $parameters): UserAccount
    {
        return new self($id, $lastLoginAt, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicId(): ResourceOwnerId
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        Assertion::string($key);

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
     * {@inheritdoc}
     */
    public function getLastLoginAt(): \DateTimeImmutable
    {
        return $this->lastLoginAt;
    }
}
