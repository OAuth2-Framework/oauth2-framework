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

use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface;

final class UserAccountRepository implements UserAccountRepositoryInterface
{
    /**
     * @var UserAccount[]
     */
    private $userAccounts = [];

    /**
     * UserAccountRepository constructor.
     */
    public function __construct()
    {
        $this->userAccounts['john.1'] = UserAccount::create(
            UserAccountId::create('john.1'),
            new \DateTimeImmutable('now -10 minutes'),
            [
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
                'updated_at' => 1485431232,
                'zoneinfo' => 'Europe/Paris',
                'locale' => 'en',
                'picture' => 'https://www.google.com',
                'amr', ['password' => 'otp'],
                'birthdate' => '1950-01-01',
                'email' => 'root@localhost.com',
                'email_verified' => false,
                'last_login_at' => time() - 100,
                'website' => 'https://john.doe.com',
                'website#fr_fr' => 'https://john.doe.fr',
                'website#fr' => 'https://john.doe.fr',
                'picture#de' => 'https://john.doe.de/picture',
            ]
        );
        $this->userAccounts['john.2'] = UserAccount::create(
            UserAccountId::create('john.2'),
            new \DateTimeImmutable('now -10 minutes'),
            [
                'password' => 'doe',
                'user' => 'john',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByUsername(string $username)
    {
        return $this->findUserAccount(UserAccountId::create($username));
    }

    /**
     * {@inheritdoc}
     */
    public function findUserAccount(UserAccountId $publicId)
    {
        return isset($this->userAccounts[$publicId->getValue()]) ? $this->userAccounts[$publicId->getValue()] : null;
    }
}
