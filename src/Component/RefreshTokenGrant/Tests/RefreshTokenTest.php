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

namespace OAuth2Framework\Component\RefreshTokenGrant\Tests;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use PHPUnit\Framework\TestCase;

/**
 * @group RefreshToken
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
        static::assertEquals('"REFRESH_TOKEN_ID"', \json_encode($refreshTokenId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
            new DataBag([
                'refresh_token_id' => 'REFRESH_TOKEN_ID',
            ]),
            new DataBag([]),
            new \DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $refreshToken->addAccessToken(new AccessTokenId('ACCESS_TOKEN_ID'));
        $refreshToken->markAsRevoked();

        static::assertInstanceOf(RefreshToken::class, $refreshToken);
        static::assertEquals('{"expires_at":1264683600,"client_id":"CLIENT_ID","parameters":{"refresh_token_id":"REFRESH_TOKEN_ID"},"metadatas":{},"is_revoked":true,"resource_owner_id":"CLIENT_ID","resource_owner_class":"OAuth2Framework\\\\Component\\\\Core\\\\Client\\\\ClientId","resource_server_id":"RESOURCE_SERVER_ID","refresh_token_id":"REFRESH_TOKEN_ID","access_token_ids":["ACCESS_TOKEN_ID"]}', \json_encode($refreshToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        static::assertEquals('REFRESH_TOKEN_ID', $refreshToken->getTokenId()->getValue());
    }
}
