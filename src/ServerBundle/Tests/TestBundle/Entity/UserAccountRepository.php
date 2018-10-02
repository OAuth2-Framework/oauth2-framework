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

use OAuth2Framework\Component\Core\UserAccount\UserAccount as BaseUserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository as UserAccountRepositoryInterface;

class UserAccountRepository implements UserAccountRepositoryInterface
{
    /**
     * @var UserAccount[]
     */
    private $userAccounts = [];

    public function __construct()
    {
        foreach ($this->getUsers() as $data) {
            $userAccount = new UserAccount(
                new UserAccountId($data['id']),
                $data['parameters']
            );
            $this->userAccounts[$data['id']] = $userAccount;
        }
    }

    public function find(UserAccountId $publicId): ?BaseUserAccount
    {
        return \array_key_exists($publicId->getValue(), $this->userAccounts) ? $this->userAccounts[$publicId->getValue()] : null;
    }

    private function getUsers(): array
    {
        return [
            [
                'id' => 'john.1',
                'parameters' => [
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
            ],
        ];
    }
}
