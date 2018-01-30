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

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\ClaimSource;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

interface ClaimSource
{
    /**
     * @param UserAccount $userAccount
     * @param string[]    $scope
     * @param array       $claims
     *
     * @return Source|null
     */
    public function getUserInfo(UserAccount $userAccount, array $scope, array $claims): ?Source;
}
