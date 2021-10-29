<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

interface Claim
{
    public function name(): string;

    public function isAvailableForUserAccount(UserAccount $userAccount, ?string $claimLocale): bool;

    /**
     * @return mixed|null
     */
    public function getForUserAccount(UserAccount $userAccount, ?string $claimLocale);
}
