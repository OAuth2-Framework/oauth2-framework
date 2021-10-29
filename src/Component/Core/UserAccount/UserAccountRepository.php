<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\UserAccount;

interface UserAccountRepository
{
    public function find(UserAccountId $publicId): ?UserAccount;
}
