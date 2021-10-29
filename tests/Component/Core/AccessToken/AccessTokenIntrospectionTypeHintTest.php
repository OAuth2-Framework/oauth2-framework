<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\AccessToken;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenIntrospectionTypeHint;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class AccessTokenIntrospectionTypeHintTest extends TestCase
{
    use ProphecyTrait;

    private ?AccessTokenIntrospectionTypeHint $accessTokenTypeHint = null;

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
        static::assertNull($this->getAccessTokenIntrospectionTypeHint()->find('UNKNOWN_TOKEN_ID'));
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

    public function getAccessTokenIntrospectionTypeHint(): AccessTokenIntrospectionTypeHint
    {
        if ($this->accessTokenTypeHint === null) {
            $accessToken = new AccessToken(
                new AccessTokenId('ACCESS_TOKEN_ID'),
                new ClientId('CLIENT_ID'),
                new UserAccountId('USER_ACCOUNT_ID'),
                new DateTimeImmutable('now +1hour'),
                new DataBag([
                    'scope' => 'scope1 scope2',
                ]),
                new DataBag([]),
                new ResourceServerId('RESOURCE_SERVER_ID')
            );
            $accessTokenRepository = $this->prophesize(AccessTokenRepository::class);
            $accessTokenRepository->find(Argument::type(AccessTokenId::class))->will(function ($args) use (
                $accessToken
            ) {
                if ($args[0]->getValue() === 'ACCESS_TOKEN_ID') {
                    return $accessToken;
                }
            });
            $this->accessTokenTypeHint = new AccessTokenIntrospectionTypeHint($accessTokenRepository->reveal());
        }

        return $this->accessTokenTypeHint;
    }
}
