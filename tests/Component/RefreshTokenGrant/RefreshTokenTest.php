<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\RefreshTokenGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class RefreshTokenTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnRefreshTokenId(): void
    {
        $refreshTokenId = RefreshTokenId::create('REFRESH_TOKEN_ID');

        static::assertInstanceOf(RefreshTokenId::class, $refreshTokenId);
        static::assertSame('REFRESH_TOKEN_ID', $refreshTokenId->getValue());
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnRefreshToken(): void
    {
        $refreshToken = new RefreshToken(
            RefreshTokenId::create('REFRESH_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            new DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            DataBag::create([
                'refresh_token_id' => 'REFRESH_TOKEN_ID',
            ]),
            DataBag::create([]),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $refreshToken->addAccessToken(AccessTokenId::create('ACCESS_TOKEN_ID'));
        $refreshToken->markAsRevoked();

        static::assertInstanceOf(RefreshToken::class, $refreshToken);
        static::assertSame('REFRESH_TOKEN_ID', $refreshToken->getId()->getValue());
    }
}
