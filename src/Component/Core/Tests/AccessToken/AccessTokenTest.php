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

namespace OAuth2Framework\Component\Core\Tests\AccessToken;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use PHPUnit\Framework\TestCase;

/**
 * @group AccessToken
 */
final class AccessTokenTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnAccessTokenId()
    {
        $accessTokenId = AccessTokenId::create('ACCESS_TOKEN_ID');

        self::assertInstanceOf(AccessTokenId::class, $accessTokenId);
        self::assertEquals('ACCESS_TOKEN_ID', $accessTokenId->getValue());
        self::assertEquals('"ACCESS_TOKEN_ID"', \json_encode($accessTokenId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnAccessToken()
    {
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            AccessTokenId::create('ACCESS_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'refresh_token_id' => 'REFRESH_TOKEN_ID',
            ]),
            DataBag::create([]),
            new \DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $accessToken = $accessToken->markAsRevoked();

        self::assertInstanceOf(AccessToken::class, $accessToken);
        self::assertEquals('{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/model/access-token/1.0/schema","type":"OAuth2Framework\\\\Component\\\\Core\\\\AccessToken\\\\AccessToken","expires_at":1264683600,"client_id":"CLIENT_ID","parameters":{"refresh_token_id":"REFRESH_TOKEN_ID"},"metadatas":{},"is_revoked":true,"resource_owner_id":"CLIENT_ID","resource_owner_class":"OAuth2Framework\\\\Component\\\\Core\\\\Client\\\\ClientId","resource_server_id":"RESOURCE_SERVER_ID","access_token_id":"ACCESS_TOKEN_ID"}', \json_encode($accessToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        self::assertEquals('ACCESS_TOKEN_ID', $accessToken->getTokenId()->getValue());
    }
}
