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

namespace OAuth2Framework\Component\AuthorizationEndpoint\UserAccountDiscovery;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\RedirectToLoginPageException;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

final class LoginParameterChecker implements UserAccountDiscovery
{
    /**
     * {@inheritdoc}
     */
    public function find(Authorization $authorization, ?bool &$isFullyAuthenticated = null): ?UserAccount
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function check(Authorization $authorization)
    {
        if ($authorization->hasPrompt('login') && !$authorization->isUserAccountFullyAuthenticated()) {
            throw new RedirectToLoginPageException($authorization);
        }
    }
}