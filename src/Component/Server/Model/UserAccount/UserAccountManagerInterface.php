<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Model\UserAccount;

interface UserAccountManagerInterface
{
    /**
     * Check if the user account password is valid.
     *
     * @param UserAccountInterface $user     The user account
     * @param string               $password Password
     *
     * @return bool
     */
    public function isPasswordCredentialValid(UserAccountInterface $user, string $password): bool;
}
