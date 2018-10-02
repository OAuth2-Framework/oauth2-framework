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
use OAuth2Framework\Component\Core\UserAccount\UserAccount as UserAccountInterface;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class UserAccount implements UserAccountInterface
{
    private $userAccountId;
    private $data = [];

    public function __construct(UserAccountId $userAccountId, array $data)
    {
        $this->userAccountId = $userAccountId;
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
        return array_key_exists($key, $this->data);
    }

    public function get(string $key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(\Safe\sprintf('The user account parameter "%s" does not exist.', $key));
        }

        return $this->data[$key];
    }
}
