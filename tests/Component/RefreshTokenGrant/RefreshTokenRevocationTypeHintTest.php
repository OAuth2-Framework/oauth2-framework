<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\RefreshTokenGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRevocationTypeHint;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class RefreshTokenRevocationTypeHintTest extends TestCase
{
    use ProphecyTrait;

    private ?RefreshTokenRevocationTypeHint $refreshTokenTypeHint = null;

    protected function setUp(): void
    {
        if (! interface_exists(TokenTypeHint::class)) {
            static::markTestSkipped('The component "oauth2-framework/token-revocation-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame('refresh_token', $this->getRefreshTokenTypeHint()->hint());
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndRevokeIt(): void
    {
        static::assertNull($this->getRefreshTokenTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $refreshToken = $this->getRefreshTokenTypeHint()
            ->find('REFRESH_TOKEN_ID')
        ;
        $this->getRefreshTokenTypeHint()
            ->revoke($refreshToken)
        ;
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function aRevokedTokenCannotBeRevokedTwice(): void
    {
        $refreshToken = $this->getRefreshTokenTypeHint()
            ->find('REVOKED_REFRESH_TOKEN_ID')
        ;
        $this->getRefreshTokenTypeHint()
            ->revoke($refreshToken)
        ;
        static::assertTrue(true);
    }

    public function getRefreshTokenTypeHint(): RefreshTokenRevocationTypeHint
    {
        if ($this->refreshTokenTypeHint === null) {
            $refreshToken = new RefreshToken(
                new RefreshTokenId('REFRESH_TOKEN_ID'),
                new ClientId('CLIENT_ID'),
                new UserAccountId('USER_ACCOUNT_ID'),
                new DateTimeImmutable('now +1hour'),
                new DataBag([
                    'scope' => 'scope1 scope2',
                ]),
                new DataBag([]),
                new ResourceServerId('RESOURCE_SERVER_ID')
            );
            $revokedRefreshToken = new RefreshToken(
                new RefreshTokenId('REVOKED_REFRESH_TOKEN_ID'),
                new ClientId('CLIENT_ID'),
                new UserAccountId('USER_ACCOUNT_ID'),
                new DateTimeImmutable('now +1hour'),
                new DataBag([
                    'scope' => 'scope1 scope2',
                ]),
                new DataBag([]),
                new ResourceServerId('RESOURCE_SERVER_ID')
            );
            $revokedRefreshToken->markAsRevoked();
            $expiredRefreshToken = new RefreshToken(
                new RefreshTokenId('EXPIRED_REFRESH_TOKEN_ID'),
                new ClientId('CLIENT_ID'),
                new UserAccountId('USER_ACCOUNT_ID'),
                new DateTimeImmutable('now -1hour'),
                new DataBag([
                    'scope' => 'scope1 scope2',
                ]),
                new DataBag([]),
                new ResourceServerId('RESOURCE_SERVER_ID')
            );
            $refreshTokenRepository = $this->prophesize(RefreshTokenRepository::class);
            $refreshTokenRepository->save(Argument::type(RefreshToken::class))->will(function () {
            });
            $refreshTokenRepository->find(new RefreshTokenId('REFRESH_TOKEN_ID'))
                ->willReturn($refreshToken)
            ;
            $refreshTokenRepository->find(new RefreshTokenId('EXPIRED_REFRESH_TOKEN_ID'))
                ->willReturn($expiredRefreshToken)
            ;
            $refreshTokenRepository->find(new RefreshTokenId('REVOKED_REFRESH_TOKEN_ID'))
                ->willReturn($revokedRefreshToken)
            ;
            $refreshTokenRepository->find(new RefreshTokenId('UNKNOWN_TOKEN_ID'))
                ->willReturn(null)
            ;
            $this->refreshTokenTypeHint = new RefreshTokenRevocationTypeHint($refreshTokenRepository->reveal());
        }

        return $this->refreshTokenTypeHint;
    }
}
