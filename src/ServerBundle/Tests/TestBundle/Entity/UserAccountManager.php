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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountManager as UserAccountManagerInterface;

class UserAccountManager implements UserAccountManagerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isPasswordCredentialValid(UserAccount $user, string $password): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        return in_array($password, $user->getOAuth2Passwords());
    }
}
