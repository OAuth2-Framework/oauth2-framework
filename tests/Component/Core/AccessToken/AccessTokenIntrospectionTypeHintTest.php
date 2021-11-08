<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\AccessToken;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\AccessToken;

/**
 * @internal
 */
final class AccessTokenIntrospectionTypeHintTest extends OAuth2TestCase
{
    protected function setUp(): void
    {
        if (! interface_exists(TokenTypeHint::class)) {
            static::markTestSkipped('The component "oauth2-framework/token-type" is not installed.');
        }
    }

    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame('access_token', $this->getAccessTokenIntrospectionTypeHint()->hint());
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndReturnValues(): void
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

        $accessToken = $this->getAccessTokenIntrospectionTypeHint()
            ->find('ACCESS_TOKEN_ID')
        ;
        static::assertInstanceOf(AccessToken::class, $accessToken);
        $introspection = $this->getAccessTokenIntrospectionTypeHint()
            ->introspect($accessToken)
        ;
        static::assertArrayHasKey('active', $introspection);
        static::assertTrue($introspection['active']);
    }
}
