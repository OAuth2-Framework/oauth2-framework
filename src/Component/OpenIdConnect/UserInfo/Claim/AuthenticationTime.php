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

final class AuthenticationTime implements Claim
{
    private const CLAIM_NAME = 'auth_time';

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
    public function isAvailableForUserAccount(UserAccount $userAccount, ?string $claimLocale): bool
    {
        return null !== $userAccount->getLastLoginAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getForUserAccount(UserAccount $userAccount, ?string $claimLocale)
    {
        return $userAccount->getLastLoginAt();
    }
}
