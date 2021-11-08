<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRegistrationEndpoint;

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class InitialAccessTokenTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnInitialAccessTokenId(): void
    {
        $initialAccessTokenId = InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_ID');

        static::assertInstanceOf(InitialAccessTokenId::class, $initialAccessTokenId);
        static::assertSame('INITIAL_ACCESS_TOKEN_ID', $initialAccessTokenId->getValue());
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnInitialAccessToken(): void
    {
        $initialAccessToken = new InitialAccessToken(
            InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            null
        );
        $initialAccessToken = $initialAccessToken->markAsRevoked();

        static::assertInstanceOf(InitialAccessToken::class, $initialAccessToken);
        static::assertSame('INITIAL_ACCESS_TOKEN_ID', $initialAccessToken->getId()->getValue());
        static::assertTrue($initialAccessToken->isRevoked());
    }
}
