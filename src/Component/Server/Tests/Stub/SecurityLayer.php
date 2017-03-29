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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Exception\RedirectToLoginPageException;
use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\UserAccountDiscoveryInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SecurityLayer implements UserAccountDiscoveryInterface
{
    /**
     * @var null|UserAccountInterface
     */
    private $userAccount = null;

    /**
     * @var null|bool
     */
    private $isFullyAuthenticated = null;

    /**
     * @param null|UserAccountInterface $userAccount
     * @param null|bool                 $isFullyAuthenticated
     */
    public function setUserAccount($userAccount, $isFullyAuthenticated)
    {
        $this->userAccount = $userAccount;
        if (null !== $userAccount) {
            Assertion::boolean($isFullyAuthenticated);
            $this->isFullyAuthenticated = $isFullyAuthenticated;
        } else {
            $this->isFullyAuthenticated = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function find(ServerRequestInterface $request, Authorization $authorization, callable $next): Authorization
    {
        if (null !== $this->userAccount) {
            if (null !== $authorization->getUserAccount()) {
                if ($this->userAccount->getPublicId()->getValue() !== $authorization->getUserAccount()->getPublicId()->getValue()) {
                    throw new RedirectToLoginPageException($authorization);
                }
            }
            $authorization = $authorization->withUserAccount($this->userAccount, $this->isFullyAuthenticated);
        }

        return $next($request, $authorization);
    }
}
