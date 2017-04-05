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

interface UserAccountRepositoryInterface
{
    /**
     * Get the user account with the specified User Account Name.
     *
     * @param string $username User Account Name
     *
     * @return UserAccountInterface|null
     */
    public function findOneByUsername(string $username);

    /**
     * Get the user account with the specified public ID.
     *
     * @param UserAccountId $publicId Public ID
     *
     * @return UserAccountInterface|null
     */
    public function findUserAccount(UserAccountId $publicId);
}
