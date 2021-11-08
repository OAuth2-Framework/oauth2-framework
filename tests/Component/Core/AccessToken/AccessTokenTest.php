<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\AccessToken;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\AccessToken;

/**
 * @internal
 */
final class AccessTokenTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnAccessTokenId(): void
    {
        $accessTokenId = AccessTokenId::create('ACCESS_TOKEN_ID');

        static::assertInstanceOf(AccessTokenId::class, $accessTokenId);
        static::assertSame('ACCESS_TOKEN_ID', $accessTokenId->getValue());
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnAccessToken(): void
    {
        $accessToken = new AccessToken(
            AccessTokenId::create('ACCESS_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            new DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            DataBag::create([
                'refresh_token_id' => 'REFRESH_TOKEN_ID',
            ]),
            DataBag::create([]),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $accessToken->markAsRevoked();

        static::assertInstanceOf(AccessToken::class, $accessToken);
        static::assertSame('ACCESS_TOKEN_ID', $accessToken->getId()->getValue());
    }
}
