<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\AccessToken;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\AccessToken;

/**
 * @internal
 */
final class AccessTokenRevocationTypeHintTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame('access_token', $this->getAccessTokenRevocationTypeHint()->hint());
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndRevokeIt(): void
    {
        $accessToken = AccessToken::create(
            AccessTokenId::create('ACCESS_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            new DateTimeImmutable('now +1 month'),
            DataBag::create(),
            DataBag::create(),
            null
        );
        $this->getAccessTokenRepository()
            ->save($accessToken)
        ;
        $accessToken = $this->getAccessTokenRevocationTypeHint()
            ->find('ACCESS_TOKEN_ID')
        ;
        static::assertInstanceOf(AccessToken::class, $accessToken);
        static::assertFalse($accessToken->isRevoked());
        $this->getAccessTokenRevocationTypeHint()
            ->revoke($accessToken)
        ;

        $accessToken = $this->getAccessTokenRevocationTypeHint()
            ->find('ACCESS_TOKEN_ID')
        ;
        static::assertTrue($accessToken->isRevoked());
    }
}
