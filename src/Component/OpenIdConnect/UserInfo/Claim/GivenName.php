<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

final class GivenName implements Claim
{
    private const CLAIM_NAME = 'given_name';

    public function name(): string
    {
        return self::CLAIM_NAME;
    }

    public function isAvailableForUserAccount(UserAccount $userAccount, ?string $claimLocale): bool
    {
        return $userAccount->has($this->getComputedClaimName($claimLocale));
    }

    public function getForUserAccount(UserAccount $userAccount, ?string $claimLocale)
    {
        return $userAccount->get($this->getComputedClaimName($claimLocale));
    }

    private function getComputedClaimName(?string $claimLocale): string
    {
        return $claimLocale !== null ? sprintf('%s#%s', self::CLAIM_NAME, $claimLocale) : self::CLAIM_NAME;
    }
}
