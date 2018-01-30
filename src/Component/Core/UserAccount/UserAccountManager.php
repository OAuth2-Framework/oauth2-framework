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

namespace OAuth2Framework\Component\Core\UserAccount;

interface UserAccountManager
{
    /**
     * Check if the user account password is valid.
     *
     * @param UserAccount $user     The user account
     * @param string      $password Password
     *
     * @return bool
     */
    public function isPasswordCredentialValid(UserAccount $user, string $password): bool;
}
