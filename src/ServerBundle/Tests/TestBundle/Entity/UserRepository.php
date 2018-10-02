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

class UserRepository
{
    /**
     * @var User[]
     */
    private $users = [];

    public function __construct()
    {
        foreach ($this->getUsers() as $data) {
            $user = new User(
                $data['username'],
                $data['roles'],
                $data['account_ids'],
                $data['last_login_at'],
                $data['last_update_at']
            );

            $this->users[$data['username']] = $user;
        }
    }

    public function findOneByUsername(string $username): ?User
    {
        return $this->users[$username] ?? null;
    }

    private function getUsers(): array
    {
        return [
        ];
    }
}
