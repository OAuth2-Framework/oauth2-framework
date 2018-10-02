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

use OAuth2Framework\Component\Core\User\User;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

interface Claim
{
    public function name(): string;

    public function isAvailableForUserAccount(User $user, UserAccount $userAccount, ?string $claimLocale): bool;

    /**
     * @return null|mixed
     */
    public function getForUserAccount(User $user, UserAccount $userAccount, ?string $claimLocale);
}
