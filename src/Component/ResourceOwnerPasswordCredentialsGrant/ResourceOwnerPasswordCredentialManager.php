<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant;

use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;

interface ResourceOwnerPasswordCredentialManager
{
    public function findResourceOwnerIdWithUsernameAndPassword(string $username, string $password): ?ResourceOwnerId;
}
