<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant;

interface ResourceOwnerWithPasswordCredential
{
    public function hasPasswordCredential(string $password): bool;
}
