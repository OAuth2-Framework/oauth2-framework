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

class UserAccountCheckerManager
{
    /**
     * @var UserAccountChecker[]
     */
    private $checkers = [];

    /**
     * @param UserAccountChecker $checker
     */
    public function add(UserAccountChecker $checker)
    {
        $this->checkers[] = $checker;
    }

    /**
     * @param Authorization $authorization
     */
    public function check(Authorization $authorization)
    {
        foreach ($this->checkers as $checker) {
            $checker->check($authorization);
        }
    }
}