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

final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
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

    public function find(RefreshTokenId $refreshTokenId): ?RefreshToken
    {
        return \array_key_exists($refreshTokenId->getValue(), $this->refreshTokens) ? $this->refreshTokens[$refreshTokenId->getValue()] : null;
    }

    public function save(RefreshToken $refreshToken): void
    {
        $this->refreshTokens[$refreshToken->getTokenId()->getValue()] = $refreshToken;
    }

    private function initRefreshTokens()
    {
        $refreshToken = new RefreshToken(
            new RefreshTokenId('VALID_REFRESH_TOKEN'),
            new ClientId('CLIENT_ID_3'),
            new UserAccountId('john.1'),
            new DataBag([]),
            new DataBag([]),
            new \DateTimeImmutable('now +1 day'),
            null
        );
        $this->save($refreshToken);

        $refreshToken = new RefreshToken(
            new RefreshTokenId('REVOKED_REFRESH_TOKEN'),
            new ClientId('CLIENT_ID_3'),
            new UserAccountId('john.1'),
            new DataBag([]),
            new DataBag([]),
            new \DateTimeImmutable('now +1 day'),
            null
        );
        $refreshToken->markAsRevoked();
        $this->save($refreshToken);

        $refreshToken = new RefreshToken(
            new RefreshTokenId('EXPIRED_REFRESH_TOKEN'),
            new ClientId('CLIENT_ID_3'),
            new UserAccountId('john.1'),
            new DataBag([]),
            new DataBag([]),
            new \DateTimeImmutable('now -1 day'),
            null
        );
        $this->save($refreshToken);
    }
}
