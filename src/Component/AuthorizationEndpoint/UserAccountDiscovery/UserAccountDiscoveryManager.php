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

class UserAccountDiscoveryManager
{
    /**
     * @var UserAccountDiscovery[]
     */
    private $extensions = [];

    /**
     * @param UserAccountDiscovery $extension
     */
    public function add(UserAccountDiscovery $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @param Authorization $authorization
     *
     * @return Authorization
     *
     * @throws RedirectToLoginPageException
     */
    public function find(Authorization $authorization): Authorization
    {
        $userAccount = null;
        $isFullyAuthenticated = null;
        foreach ($this->extensions as $extension) {
            $tmpIsFullyAuthenticated = null;
            $tmpUserAccount = $extension->find($authorization, $tmpIsFullyAuthenticated);
            if (null !== $tmpUserAccount) {
                if (null === $userAccount) {
                    $userAccount = $tmpUserAccount;
                    $isFullyAuthenticated = $tmpIsFullyAuthenticated;
                } else {
                    if ($tmpUserAccount->getPublicId()->getValue() !== $userAccount->getPublicId()->getValue()) {
                        throw new RedirectToLoginPageException($authorization);
                    }
                    if (true === $tmpIsFullyAuthenticated) {
                        $isFullyAuthenticated = $tmpIsFullyAuthenticated;
                    }
                }
            }
        }

        if (null !== $userAccount) {
            $authorization = $authorization->withUserAccount($userAccount, $isFullyAuthenticated);
        }

        return $authorization;
    }

    /**
     * @param Authorization $authorization
     */
    public function check(Authorization $authorization)
    {
        foreach ($this->extensions as $extension) {
            $authorization = $extension->check($authorization);
        }
    }
}
