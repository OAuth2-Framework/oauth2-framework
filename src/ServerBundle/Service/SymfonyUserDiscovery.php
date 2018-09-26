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

namespace OAuth2Framework\ServerBundle\Service;

use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountDiscovery;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SymfonyUserDiscovery implements UserAccountDiscovery
{
    private $tokenStorage;

    private $authorizationChecker;

    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function find(?bool &$isFullyAuthenticated = null): ?UserAccount
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        $userAccount = $token->getUser();
        if (!$userAccount instanceof UserAccount) {
            return null;
        }

        return $userAccount;
    }

    public function isFullyAuthenticated(): bool
    {
        return $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY');
    }
}
