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

namespace OAuth2Framework\Component\Core\Tests\AccessToken;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use PHPUnit\Framework\TestCase;

/**
 * @group AccessToken
 *
 * @internal
 * @coversNothing
 */
final class AccessTokenTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnAccessTokenId()
    {
        $accessTokenId = new AccessTokenId('ACCESS_TOKEN_ID');

        static::assertInstanceOf(AccessTokenId::class, $accessTokenId);
        static::assertEquals('ACCESS_TOKEN_ID', $accessTokenId->getValue());
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnAccessToken()
    {
        $accessToken = new AccessToken(
            new AccessTokenId('ACCESS_TOKEN_ID'),
            new ClientId('CLIENT_ID'),
            new ClientId('CLIENT_ID'),
            new \DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            new DataBag([
                'refresh_token_id' => 'REFRESH_TOKEN_ID',
            ]),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $accessToken->markAsRevoked();

        static::assertInstanceOf(AccessToken::class, $accessToken);
        static::assertEquals('ACCESS_TOKEN_ID', $accessToken->getId()->getValue());
    }
}
