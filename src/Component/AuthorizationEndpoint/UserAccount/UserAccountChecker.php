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
use OAuth2Framework\Component\AuthorizationEndpoint\Exception;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

interface UserAccountChecker
{
    /**
     * @param null|UserAccount $userAccount
     *
     * @throws Exception\CreateRedirectionException
     * @throws Exception\ProcessAuthorizationException
     * @throws Exception\RedirectToLoginPageException
     * @throws Exception\ShowConsentScreenException
     */
    public function check(Authorization $authorization, ?UserAccount $userAccount, bool $isFullyAuthenticated): void;
}
