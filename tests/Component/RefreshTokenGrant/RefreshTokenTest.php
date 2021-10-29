<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\RefreshTokenGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RefreshTokenTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnRefreshTokenId(): void
    {
        $refreshTokenId = new RefreshTokenId('REFRESH_TOKEN_ID');

        static::assertInstanceOf(RefreshTokenId::class, $refreshTokenId);
        static::assertSame('REFRESH_TOKEN_ID', $refreshTokenId->getValue());
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnRefreshToken(): void
    {
        $refreshToken = new RefreshToken(
            new RefreshTokenId('REFRESH_TOKEN_ID'),
            new ClientId('CLIENT_ID'),
            new ClientId('CLIENT_ID'),
            new DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            new DataBag([
                'refresh_token_id' => 'REFRESH_TOKEN_ID',
            ]),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $refreshToken->addAccessToken(new AccessTokenId('ACCESS_TOKEN_ID'));
        $refreshToken->markAsRevoked();

        static::assertInstanceOf(RefreshToken::class, $refreshToken);
        static::assertSame('REFRESH_TOKEN_ID', $refreshToken->getId()->getValue());
    }
}
