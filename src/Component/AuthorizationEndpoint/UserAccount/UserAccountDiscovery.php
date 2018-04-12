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

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

interface UserAccountDiscovery
{
    /**
     * @param bool $isFullyAuthenticated
     *
     * @return null|UserAccount
     */
    public function find(?bool &$isFullyAuthenticated = null): ?UserAccount;
}
