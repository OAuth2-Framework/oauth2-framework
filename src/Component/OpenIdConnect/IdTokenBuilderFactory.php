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

namespace OAuth2Framework\Component\OpenIdConnect;

use Jose\Component\KeyManagement\JKUFactory;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\User\User;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;

class IdTokenBuilderFactory
{
    private $issuer;
    private $userinfo;
    private $lifetime;

    /**
     * @var null|JKUFactory
     */
    private $jkuFactory = null;

    /**
     * @var null|AuthorizationCodeRepository
     */
    private $authorizationCodeRepository = null;

    public function __construct(string $issuer, UserInfo $userinfo, int $lifetime)
    {
        $this->issuer = $issuer;
        $this->userinfo = $userinfo;
        $this->lifetime = $lifetime;
    }

    public function createBuilder(Client $client, User $user, UserAccount $userAccount, string $redirectUri)
    {
        return new IdTokenBuilder($this->issuer, $this->userinfo, $this->lifetime, $client, $user, $userAccount, $redirectUri, $this->jkuFactory, $this->authorizationCodeRepository);
    }

    public function enableJkuSupport(JKUFactory $jkuFactory): void
    {
        $this->jkuFactory = $jkuFactory;
    }

    public function enableAuthorizationCodeSupport(AuthorizationCodeRepository $authorizationCodeRepository): void
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;
    }
}
