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

use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository as UserAccountRepositoryInterface;

class UserAccountRepository implements UserAccountRepositoryInterface
{
    private $usersByUsername = [];

    private $usersByPublicId = [];

    public function __construct()
    {
        foreach ($this->getUsers() as $userInformation) {
            $user = new User(
                $userInformation['username'],
                $userInformation['password'],
                $userInformation['salt'],
                $userInformation['roles'],
                $userInformation['oauth2Passwords'],
                $userInformation['public_id'],
                $userInformation['last_login_at'],
                $userInformation['last_update_at'],
                $userInformation['parameters']
            );
            $this->usersByUsername[$userInformation['username']] = $user;
            $this->usersByPublicId[$userInformation['public_id']->getValue()] = $user;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByUsername(string $username): ?UserAccount
    {
        return array_key_exists($username, $this->usersByUsername) ? $this->usersByUsername[$username] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function find(UserAccountId $publicId): ?UserAccount
    {
        return array_key_exists($publicId->getValue(), $this->usersByPublicId) ? $this->usersByPublicId[$publicId->getValue()] : null;
    }

    /**
     * @return array
     */
    private function getUsers(): array
    {
        return [
            [
                'id' => 'john.1',
                'public_id' => UserAccountId::create('john.1'),
                'username' => 'john.1',
                'password' => 'secret',
                'salt' => null,
                'roles' => ['ROLE_USER'],
                'last_login_at' => new \DateTimeImmutable('now -100 seconds'),
                'last_update_at' => new \DateTimeImmutable('now -2 hours'),
                'amr' => ['password' => 'otp'],
                'acr' => 0,
                'parameters' => [
                    'password' => 'doe',
                    'user' => 'john',
                    'address', [
                        'street_address' => '5 rue Sainte Anne',
                        'region' => 'Île de France',
                        'postal_code' => '75001',
                        'locality' => 'Paris',
                        'country' => 'France',
                    ],
                    'name' => 'John Doe',
                    'given_name' => 'John',
                    'family_name' => 'Doe',
                    'middle_name' => 'Jack',
                    'nickname' => 'Little John',
                    'profile' => 'https://profile.doe.fr/john/',
                    'preferred_username' => 'j-d',
                    'gender' => 'M',
                    'phone_number' => '+0123456789',
                    'phone_number_verified' => true,
                    'zoneinfo' => 'Europe/Paris',
                    'locale' => 'en',
                    'picture' => 'https://www.google.com',
                    'birthdate' => '1950-01-01',
                    'email' => 'root@localhost.com',
                    'email_verified' => false,
                    'website' => 'https://john.doe.com',
                    'website#fr_fr' => 'https://john.doe.fr',
                    'website#fr' => 'https://john.doe.fr',
                    'picture#de' => 'https://john.doe.de/picture',
                ],
                'oauth2Passwords' => ['password.1'],
            ],
            [
                'id' => 'john.2',
                'public_id' => UserAccountId::create('john.2'),
                'username' => 'john.2',
                'password' => 'secret',
                'salt' => null,
                'roles' => ['ROLE_USER'],
                'last_login_at' => new \DateTimeImmutable('now -100 seconds'),
                'last_update_at' => null,
                'parameters' => [
                    'password' => 'doe',
                    'user' => 'john',
                ],
                'oauth2Passwords' => ['doe'],
            ],
        ];
    }
}
