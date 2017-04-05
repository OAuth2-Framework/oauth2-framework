<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Security\Authentication\Token;

use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface UserAccountTokenInterface extends TokenInterface
{
    /**
     * Returns a user account.
     *
     * @return UserAccountInterface
     */
    public function getUserAccount();

    /**
     * Sets a user account.
     *
     * @param UserAccountInterface $user_account
     */
    public function setUserAccount(UserAccountInterface $user_account);
}
