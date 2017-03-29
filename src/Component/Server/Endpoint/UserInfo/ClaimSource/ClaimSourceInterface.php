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

namespace OAuth2Framework\Component\Server\Endpoint\UserInfo\ClaimSource;

use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;

interface ClaimSourceInterface
{
    /**
     * @param UserAccountInterface $userAccount
     * @param string[]             $scope
     * @param array                $claims
     *
     * @return Source|null
     */
    public function getUserInfo(UserAccountInterface $userAccount, array $scope, array $claims);
}
