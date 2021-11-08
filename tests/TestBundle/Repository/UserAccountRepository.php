<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\UserAccount\UserAccount as BaseUserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository as UserAccountRepositoryInterface;
use OAuth2Framework\Tests\TestBundle\Entity\UserAccount;

final class UserAccountRepository implements UserAccountRepositoryInterface
{
    /**
     * @var UserAccount[]
     */
    private array $userAccounts = [];

    /**
     * @var UserAccount[]
     */
    private array $usernames = [];

    public function __construct()
    {
        foreach ($this->getUsers() as $data) {
            $userAccount = new UserAccount(
                new UserAccountId($data['id']),
                $data['username'],
                $data['roles'],
                $data['last_login_at'],
                $data['last_update_at'],
                $data['parameters']
            );
            $this->userAccounts[$data['id']] = $userAccount;
            $this->usernames[$data['username']] = $userAccount;
        }
    }

    public function find(UserAccountId $publicId): ?BaseUserAccount
    {
        return $this->userAccounts[$publicId->getValue()] ?? null;
    }

    public function findOneByUsername(string $username): ?BaseUserAccount
    {
        return $this->usernames[$username] ?? null;
    }

    public function save(UserAccount $userAccount): void
    {
        $this->usernames[$userAccount->getPublicId()->getValue()] = $userAccount;
    }

    private function getUsers(): array
    {
        return [
            [
                'username' => 'admin',
                'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
                'last_login_at' => new DateTimeImmutable('now -25 hours'),
                'last_update_at' => new DateTimeImmutable('now -15 days'),
                'id' => 'john.1',
                'parameters' => [
                    'address',
                    [
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
