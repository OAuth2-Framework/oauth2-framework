<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\UserAccount;

use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;

class UserAccountId extends ResourceOwnerId
{
    public static function create(string $value): self
    {
        return new self($value);
    }
}
