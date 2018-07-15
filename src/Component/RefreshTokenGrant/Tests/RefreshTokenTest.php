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
        $refreshTokenId = RefreshTokenId::create('REFRESH_TOKEN_ID');

        self::assertInstanceOf(RefreshTokenId::class, $refreshTokenId);
        self::assertEquals('REFRESH_TOKEN_ID', $refreshTokenId->getValue());
        self::assertEquals('"REFRESH_TOKEN_ID"', \json_encode($refreshTokenId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnRefreshToken()
    {
        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create('REFRESH_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'refresh_token_id' => 'REFRESH_TOKEN_ID',
            ]),
            DataBag::create([]),
            new \DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $refreshToken = $refreshToken->addAccessToken(AccessTokenId::create('ACCESS_TOKEN_ID'));
        $refreshToken = $refreshToken->markAsRevoked();

        self::assertInstanceOf(RefreshToken::class, $refreshToken);
        self::assertEquals('{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/model/refresh-token/1.0/schema","type":"OAuth2Framework\\\\Component\\\\RefreshTokenGrant\\\\RefreshToken","expires_at":1264683600,"client_id":"CLIENT_ID","parameters":{"refresh_token_id":"REFRESH_TOKEN_ID"},"metadatas":{},"is_revoked":true,"resource_owner_id":"CLIENT_ID","resource_owner_class":"OAuth2Framework\\\\Component\\\\Core\\\\Client\\\\ClientId","resource_server_id":"RESOURCE_SERVER_ID","refresh_token_id":"REFRESH_TOKEN_ID","access_token_ids":["ACCESS_TOKEN_ID"]}', \json_encode($refreshToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        self::assertEquals('REFRESH_TOKEN_ID', $refreshToken->getTokenId()->getValue());
    }
}
