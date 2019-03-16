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

namespace OAuth2Framework\Component\ClientRegistrationEndpoint\Tests;

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;

/**
 * @group InitialAccessToken
 */
final class InitialAccessTokenTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnInitialAccessTokenId()
    {
        $initialAccessTokenId = new InitialAccessTokenId('INITIAL_ACCESS_TOKEN_ID');

        static::assertInstanceOf(InitialAccessTokenId::class, $initialAccessTokenId);
        static::assertEquals('INITIAL_ACCESS_TOKEN_ID', $initialAccessTokenId->getValue());
        static::assertEquals('"INITIAL_ACCESS_TOKEN_ID"', \Safe\json_encode($initialAccessTokenId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnInitialAccessToken()
    {
        $initialAccessToken = new InitialAccessToken(
            new InitialAccessTokenId('INITIAL_ACCESS_TOKEN_ID'),
            new UserAccountId('USER_ACCOUNT_ID'),
            null
        );
        $initialAccessToken->markAsRevoked();

        static::assertInstanceOf(InitialAccessToken::class, $initialAccessToken);
        static::assertEquals('{"initial_access_token_id":"INITIAL_ACCESS_TOKEN_ID","user_account_id":"USER_ACCOUNT_ID","expires_at":null,"is_revoked":true}', \Safe\json_encode($initialAccessToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        static::assertEquals('INITIAL_ACCESS_TOKEN_ID', $initialAccessToken->getId()->getValue());
    }
}
