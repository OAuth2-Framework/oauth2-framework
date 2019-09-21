<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\RefreshTokenGrant\Tests;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use PHPUnit\Framework\TestCase;

/**
 * @group RefreshToken
 *
 * @internal
 */
final class RefreshTokenTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnRefreshTokenId()
    {
        $refreshTokenId = new RefreshTokenId('REFRESH_TOKEN_ID');

        static::assertInstanceOf(RefreshTokenId::class, $refreshTokenId);
        static::assertEquals('REFRESH_TOKEN_ID', $refreshTokenId->getValue());
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnRefreshToken()
    {
        $refreshToken = new RefreshToken(
            new RefreshTokenId('REFRESH_TOKEN_ID'),
            new ClientId('CLIENT_ID'),
            new ClientId('CLIENT_ID'),
            new \DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            new DataBag([
                'refresh_token_id' => 'REFRESH_TOKEN_ID',
            ]),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $refreshToken->addAccessToken(new AccessTokenId('ACCESS_TOKEN_ID'));
        $refreshToken->markAsRevoked();

        static::assertInstanceOf(RefreshToken::class, $refreshToken);
        static::assertEquals('REFRESH_TOKEN_ID', $refreshToken->getId()->getValue());
    }
}
