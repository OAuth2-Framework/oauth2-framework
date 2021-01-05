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

namespace OAuth2Framework\Tests\Component\ClientRegistrationEndpoint;

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;

/**
 * @group InitialAccessToken
 *
 * @internal
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
        static::assertEquals('INITIAL_ACCESS_TOKEN_ID', $initialAccessToken->getId()->getValue());
    }
}
