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
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialManager as ResourceOwnerPasswordCredentialManagerInterface;

final class ResourceOwnerPasswordCredentialManager implements ResourceOwnerPasswordCredentialManagerInterface
{
    /**
     * @var ResourceOwnerId[]
     */
    private $usernameAndPasswords;

    public function __construct()
    {
        $this->usernameAndPasswords = [
            'password.1' => new UserAccountId('john.1'),
        ];
    }

    public function findResourceOwnerIdWithUsernameAndPassword(string $username, string $password): ?ResourceOwnerId
    {
        if (!array_key_exists($password, $this->usernameAndPasswords)) {
            return null;
        }
        if ($this->usernameAndPasswords[$password]->getValue() !== $username) {
            return null;
        }

        return $this->usernameAndPasswords[$password];
    }
}
