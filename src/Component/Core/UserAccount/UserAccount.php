<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\UserAccount;

use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;

interface UserAccount extends ResourceOwner
{
    public function getLastLoginAt(): ?int;

    public function getLastUpdateAt(): ?int;

    public function getUserAccountId(): UserAccountId;
}
