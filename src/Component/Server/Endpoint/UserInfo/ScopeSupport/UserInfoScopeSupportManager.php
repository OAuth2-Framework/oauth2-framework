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

namespace OAuth2Framework\Component\Server\Endpoint\UserInfo\ScopeSupport;

use Assert\Assertion;

final class UserInfoScopeSupportManager
{
    /**
     * @var UserInfoScopeSupportInterface[]
     */
    private $userinfoScopeSupports = [];

    /**
     * @param UserInfoScopeSupportInterface $userinfoScopeSupport
     *
     * @return UserInfoScopeSupportManager
     */
    public function add(UserInfoScopeSupportInterface $userinfoScopeSupport): UserInfoScopeSupportManager
    {
        $this->userinfoScopeSupports[$userinfoScopeSupport->getScope()] = $userinfoScopeSupport;

        return $this;
    }

    /**
     * @param string $scope
     *
     * @return bool
     */
    public function has(string $scope): bool
    {
        return array_key_exists($scope, $this->userinfoScopeSupports);
    }

    /**
     * @param string $scope
     *
     * @throws \InvalidArgumentException
     *
     * @return UserinfoScopeSupportInterface
     */
    public function get(string $scope): UserInfoScopeSupportInterface
    {
        Assertion::true($this->has($scope), sprintf('The userinfo scope \'%s\' is not supported.', $scope));

        return $this->userinfoScopeSupports[$scope];
    }
}
