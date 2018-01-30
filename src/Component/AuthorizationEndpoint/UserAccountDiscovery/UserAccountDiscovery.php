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
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

interface UserAccountDiscovery
{
    /**
     * @param Authorization $authorization
     * @param bool          $isFullyAuthenticated
     *
     * @return null|UserAccount
     */
    public function find(Authorization $authorization, ?bool &$isFullyAuthenticated = null): ?UserAccount;

    /**
     * @param Authorization $authorization
     */
    public function check(Authorization $authorization);
}
