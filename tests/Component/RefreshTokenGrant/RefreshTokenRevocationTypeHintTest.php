<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\RefreshTokenGrant;

use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class RefreshTokenRevocationTypeHintTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame('refresh_token', $this->getRefreshTokenRevocationTypeHint()->hint());
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndRevokeIt(): void
    {
        static::assertNull($this->getRefreshTokenRevocationTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $refreshToken = $this->getRefreshTokenRevocationTypeHint()
            ->find('REFRESH_TOKEN_ID')
        ;
        $this->getRefreshTokenRevocationTypeHint()
            ->revoke($refreshToken)
        ;
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function aRevokedTokenCannotBeRevokedTwice(): void
    {
        $refreshToken = $this->getRefreshTokenRevocationTypeHint()
            ->find('REVOKED_REFRESH_TOKEN_ID')
        ;
        $this->getRefreshTokenRevocationTypeHint()
            ->revoke($refreshToken)
        ;
        static::assertTrue(true);
    }
}
