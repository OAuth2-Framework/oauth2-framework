<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

final class Zoneinfo implements Claim
{
    private const CLAIM_NAME = 'zoneinfo';

    public function name(): string
    {
        return self::CLAIM_NAME;
    }

    public function isAvailableForUserAccount(UserAccount $userAccount, ?string $claimLocale): bool
    {
        return $userAccount->has(self::CLAIM_NAME);
    }

    public function getForUserAccount(UserAccount $userAccount, ?string $claimLocale)
    {
        return $userAccount->get(self::CLAIM_NAME);
    }
}
