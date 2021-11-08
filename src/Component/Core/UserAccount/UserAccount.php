<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\UserAccount;

use DateTimeInterface;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;

interface UserAccount extends ResourceOwner
{
    public function getLastLoginAt(): ?DateTimeInterface;

    public function getLastUpdateAt(): ?DateTimeInterface;

    public function getUserAccountId(): UserAccountId;
}
