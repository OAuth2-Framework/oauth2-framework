<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use function array_key_exists;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialManager as ResourceOwnerPasswordCredentialManagerInterface;

final class ResourceOwnerPasswordCredentialRepository implements ResourceOwnerPasswordCredentialManagerInterface
{
    /**
     * @var ResourceOwnerId[]
     */
    private array $usernameAndPasswords;

    public function __construct()
    {
        $this->usernameAndPasswords = [
            'password.1' => new UserAccountId('john.1'),
        ];
    }

    public function findResourceOwnerIdWithUsernameAndPassword(string $username, string $password): ?ResourceOwnerId
    {
        if (! array_key_exists($password, $this->usernameAndPasswords)) {
            return null;
        }
        if ($this->usernameAndPasswords[$password]->getValue() !== $username) {
            return null;
        }

        return $this->usernameAndPasswords[$password];
    }
}
