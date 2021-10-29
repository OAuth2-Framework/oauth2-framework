<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\AccessToken;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AccessTokenTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnAccessTokenId(): void
    {
        $accessTokenId = new AccessTokenId('ACCESS_TOKEN_ID');

        static::assertInstanceOf(AccessTokenId::class, $accessTokenId);
        static::assertSame('ACCESS_TOKEN_ID', $accessTokenId->getValue());
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnAccessToken(): void
    {
        $accessToken = new AccessToken(
            new AccessTokenId('ACCESS_TOKEN_ID'),
            new ClientId('CLIENT_ID'),
            new ClientId('CLIENT_ID'),
            new DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            new DataBag([
                'refresh_token_id' => 'REFRESH_TOKEN_ID',
            ]),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $accessToken->markAsRevoked();

        static::assertInstanceOf(AccessToken::class, $accessToken);
        static::assertSame('ACCESS_TOKEN_ID', $accessToken->getId()->getValue());
    }
}
