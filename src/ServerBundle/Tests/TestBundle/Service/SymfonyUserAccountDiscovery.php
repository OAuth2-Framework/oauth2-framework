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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Service;

use Assert\Assertion;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class SymfonyUserAccountDiscovery implements UserAccountDiscovery
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getCurrentAccount(): ?UserAccount
    {
        $token = $this->tokenStorage->getToken();
        Assertion::isInstanceOf($token, TokenInterface::class, 'Unable to retrieve the current user.');

        $user = $token->getUser();
        Assertion::isInstanceOf($user, UserAccount::class, 'Unable to retrieve the current user.');

        return $user;
    }
}
