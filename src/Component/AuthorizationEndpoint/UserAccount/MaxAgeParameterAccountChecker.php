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

namespace OAuth2Framework\Component\AuthorizationEndpoint\UserAccount;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\RedirectToLoginPageException;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

final class MaxAgeParameterAccountChecker implements UserAccountChecker
{
    /**
     * {@inheritdoc}
     */
    public function check(Authorization $authorization, ?UserAccount $userAccount, bool $isFullyAuthenticated): void
    {
        if (null === $userAccount) {
            throw new RedirectToLoginPageException($authorization);
        }

        switch (true) {
            case $authorization->hasQueryParam('max_age'):
                $max_age = (int) $authorization->getQueryParam('max_age');

                break;
            case $authorization->getClient()->has('default_max_age'):
                $max_age = (int) $authorization->getClient()->get('default_max_age');

                break;
            default:
                return;
        }

        if ($authorization->isUserAccountFullyAuthenticated()) {
            return;
        }

        if (null === $userAccount->getLastLoginAt() || \time() - $userAccount->getLastLoginAt() > $max_age) {
            throw new RedirectToLoginPageException($authorization);
        }
    }
}
