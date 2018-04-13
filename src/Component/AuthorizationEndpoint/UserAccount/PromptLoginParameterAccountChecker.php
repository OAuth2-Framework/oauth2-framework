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

class PromptLoginParameterAccountChecker implements UserAccountChecker
{
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