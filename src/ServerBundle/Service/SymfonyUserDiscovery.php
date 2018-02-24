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

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccountDiscovery\UserAccountDiscovery;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SymfonyUserDiscovery implements UserAccountDiscovery
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * SymfonyUserDiscovery constructor.
     *
     * @param TokenStorageInterface         $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function find(Authorization $authorization, ?bool &$isFullyAuthenticated = null): ?UserAccount
    {
        if (null !== $token = $this->tokenStorage->getToken()) {
            $userAccount = $token->getUser();
            if ($userAccount instanceof UserAccount) {
                $isFullyAuthenticated = $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY');

                return $userAccount;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function check(Authorization $authorization)
    {
    }
}
