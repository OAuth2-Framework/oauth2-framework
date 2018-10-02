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

use OAuth2Framework\Component\AuthorizationEndpoint\User\UserDiscovery;
use OAuth2Framework\Component\Core\User\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class SymfonyUserDiscovery implements UserDiscovery
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getCurrentUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            throw new \InvalidArgumentException('Unable to retrieve the current user.');
        }

        $userAccount = $token->getUser();
        if (!$userAccount instanceof User) {
            throw new \InvalidArgumentException('Unable to retrieve the current user.');
        }

        return $userAccount;
    }
}
