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

use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\ServerBundle\Service\SymfonyUserDiscovery as BaseSymfonyUserDiscovery;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SymfonyUserDiscovery extends BaseSymfonyUserDiscovery
{
    private $session;

    public function __construct(TokenStorageInterface $tokenStorage, SessionInterface $session)
    {
        parent::__construct($tokenStorage);
        $this->session = $session;
    }

    public function getCurrentAccount(): UserAccount
    {
    }
}
