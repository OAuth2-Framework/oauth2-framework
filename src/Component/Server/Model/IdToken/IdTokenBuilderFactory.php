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

namespace OAuth2Framework\Component\Server\Model\IdToken;

use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;

final class IdTokenBuilderFactory
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
     * @var
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
     * @param Client               $client
     * @param UserAccountInterface $userAccount
     * @param string               $redirectUri
     *
     * @return IdTokenBuilder
     */
    public function createBuilder(Client $client, UserAccountInterface $userAccount, string $redirectUri)
    {
        return IdTokenBuilder::create($this->issuer, $this->userinfo, $this->lifetime, $client, $userAccount, $redirectUri);
    }
}
