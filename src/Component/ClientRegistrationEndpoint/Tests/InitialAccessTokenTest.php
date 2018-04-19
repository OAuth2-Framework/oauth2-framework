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

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessToken;
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
        $initialAccessTokenId = InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_ID');

        self::assertInstanceOf(InitialAccessTokenId::class, $initialAccessTokenId);
        self::assertEquals('INITIAL_ACCESS_TOKEN_ID', $initialAccessTokenId->getValue());
        self::assertEquals('"INITIAL_ACCESS_TOKEN_ID"', json_encode($initialAccessTokenId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnInitialAccessToken()
    {
        $initialAccessToken = InitialAccessToken::createEmpty();
        $initialAccessToken = $initialAccessToken->create(
            InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            null
        );
        $initialAccessToken = $initialAccessToken->markAsRevoked();

        self::assertInstanceOf(InitialAccessToken::class, $initialAccessToken);
        self::assertEquals('{"type":"OAuth2Framework\\\\Component\\\\ClientRegistrationEndpoint\\\\InitialAccessToken","initial_access_token_id":"INITIAL_ACCESS_TOKEN_ID","user_account_id":"USER_ACCOUNT_ID","expires_at":null,"is_revoked":true}', json_encode($initialAccessToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        self::assertEquals('INITIAL_ACCESS_TOKEN_ID', $initialAccessToken->getTokenId()->getValue());
    }
}
