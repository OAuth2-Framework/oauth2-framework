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

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

final class Gender implements Claim
{
    private const CLAIM_NAME = 'gender';

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return self::CLAIM_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailableForUserAccount(UserAccount $userAccount, ?string $claimLocale):bool
    {
        return $userAccount->has(
            $this->getComputedClaimName($claimLocale)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getForUserAccount(UserAccount $userAccount, ?string $claimLocale)
    {
        return $userAccount->get(
            $this->getComputedClaimName($claimLocale)
        );
    }

    private function getComputedClaimName(string $claimLocale): string
    {
        return $claimLocale ? sprintf('%s#%s', self::CLAIM_NAME, $claimLocale) : self::CLAIM_NAME;
    }
}
