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

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;

class IdTokenBuilderFactory
{
    /**
     * @var string
     */
    private $issuer;

    /**
     * @var UserInfo
     */
    private $userinfo;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * IdTokenBuilder constructor.
     *
     * @param string   $issuer
     * @param UserInfo $userinfo
     * @param int      $lifetime
     */
    public function __construct(string $issuer, UserInfo $userinfo, int $lifetime)
    {
        $this->issuer = $issuer;
        $this->userinfo = $userinfo;
        $this->lifetime = $lifetime;
    }

    /**
     * @param Client      $client
     * @param UserAccount $userAccount
     * @param string      $redirectUri
     *
     * @return IdTokenBuilder
     */
    public function createBuilder(Client $client, UserAccount $userAccount, string $redirectUri)
    {
        return IdTokenBuilder::create($this->issuer, $this->userinfo, $this->lifetime, $client, $userAccount, $redirectUri);
    }
}
