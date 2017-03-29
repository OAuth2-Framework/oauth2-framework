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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountManagerInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface;

final class UserAccountManager implements UserAccountManagerInterface
{
    /**
     * @var UserAccountRepositoryInterface
     */
    private $userAccountRepository;

    /**
     * UserAccountManager constructor.
     *
     * @param UserAccountRepositoryInterface $userAccountRepository
     */
    public function __construct(UserAccountRepositoryInterface $userAccountRepository)
    {
        $this->userAccountRepository = $userAccountRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordCredentialValid(UserAccountInterface $user, string $password): bool
    {
        if (!$user instanceof UserAccount || !$user->has('password')) {
            return false;
        }

        return hash_equals($password, $user->get('password'));
    }
}
