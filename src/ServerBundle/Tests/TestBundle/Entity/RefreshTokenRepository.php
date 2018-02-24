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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository as RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @var RefreshToken[]
     */
    private $refreshTokens = [];

    /**
     * RefreshTokenRepository constructor.
     */
    public function __construct()
    {
        $this->initRefreshTokens();
    }

    /**
     * {@inheritdoc}
     */
    public function find(RefreshTokenId $refreshTokenId)
    {
        return array_key_exists($refreshTokenId->getValue(), $this->refreshTokens) ? $this->refreshTokens[$refreshTokenId->getValue()] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function save(RefreshToken $refreshToken)
    {
        $this->refreshTokens[$refreshToken->getTokenId()->getValue()] = $refreshToken;
    }

    private function initRefreshTokens()
    {
        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create('VALID_REFRESH_TOKEN'),
            UserAccountId::create('john.1'),
            ClientId::create('CLIENT_ID_3'),
            DataBag::create([]),
            DataBag::create([]),
            new \DateTimeImmutable('now +1 day'),
            null
        );
        $refreshToken->eraseMessages();
        $this->save($refreshToken);

        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create('REVOKED_REFRESH_TOKEN'),
            UserAccountId::create('john.1'),
            ClientId::create('CLIENT_ID_3'),
            DataBag::create([]),
            DataBag::create([]),
            new \DateTimeImmutable('now +1 day'),
            null
        );
        $refreshToken = $refreshToken->markAsRevoked();
        $refreshToken->eraseMessages();
        $this->save($refreshToken);

        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create('EXPIRED_REFRESH_TOKEN'),
            UserAccountId::create('john.1'),
            ClientId::create('CLIENT_ID_3'),
            DataBag::create([]),
            DataBag::create([]),
            new \DateTimeImmutable('now -1 day'),
            null
        );
        $refreshToken->eraseMessages();
        $this->save($refreshToken);
    }
}
