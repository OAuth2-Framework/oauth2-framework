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

use OAuth2Framework\Component\Core\UserAccount\AuthenticationMethodReferenceSupport;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountManager;

final class AuthenticationMethodReference implements Claim
{
    private const CLAIM_NAME = 'amr';

    /**
     * @var UserAccountManager|AuthenticationMethodReferenceSupport
     */
    private $userAccountManager;

    public function __construct(UserAccountManager $userAccountManager)
    {
        $this->userAccountManager = $userAccountManager;
    }

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
        if (null === $this->userAccountManager || !$this->userAccountManager instanceof AuthenticationMethodReferenceSupport) {
            return false;
        }

        return null !== $this->userAccountManager->getAuthenticationMethodReferenceFor($userAccount);
    }

    /**
     * {@inheritdoc}
     */
    public function getForUserAccount(UserAccount $userAccount, ?string $claimLocale)
    {
        return $this->userAccountManager->getAuthenticationMethodReferenceFor($userAccount);
    }
}
