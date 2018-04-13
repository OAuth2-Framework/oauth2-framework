<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\RefreshTokenGrant\Tests;

use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenTypeHint;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group TypeHint
 * @group RefreshTokenTypeHint
 */
class RefreshTokenTypeHintTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertEquals('refresh_token', $this->getRefreshTokenTypeHint()->hint());
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndReturnValues()
    {
        self::assertNull($this->getRefreshTokenTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $refreshToken = $this->getRefreshTokenTypeHint()->find('REFRESH_TOKEN_ID');
        $introspection = $this->getRefreshTokenTypeHint()->introspect($refreshToken);
        self::arrayHasKey('active', $introspection);
        self::assertTrue($introspection['active']);
    }

    /**
     * @test
     */
    public function theTokenIsRevoked()
    {
        $refreshToken = $this->getRefreshTokenTypeHint()->find('REVOKED_REFRESH_TOKEN_ID');
        $introspection = $this->getRefreshTokenTypeHint()->introspect($refreshToken);
        self::assertEquals(['active' => false], $introspection);
    }

    /**
     * @test
     */
    public function theTokenExpired()
    {
        $refreshToken = $this->getRefreshTokenTypeHint()->find('EXPIRED_REFRESH_TOKEN_ID');
        $introspection = $this->getRefreshTokenTypeHint()->introspect($refreshToken);
        self::assertEquals(['active' => false], $introspection);
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndRevokeIt()
    {
        self::assertNull($this->getRefreshTokenTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $refreshToken = $this->getRefreshTokenTypeHint()->find('REFRESH_TOKEN_ID');
        $this->getRefreshTokenTypeHint()->revoke($refreshToken);
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function aRevokedTokenCannotBeRevokedTwice()
    {
        $refreshToken = $this->getRefreshTokenTypeHint()->find('REVOKED_REFRESH_TOKEN_ID');
        $this->getRefreshTokenTypeHint()->revoke($refreshToken);
        self::assertTrue(true);
    }

    /**
     * @var null|RefreshTokenTypeHint
     */
    private $refreshTokenTypeHint = null;

    /**
     * @return RefreshTokenTypeHint
     */
    public function getRefreshTokenTypeHint(): RefreshTokenTypeHint
    {
        if (null === $this->refreshTokenTypeHint) {
            $refreshToken = RefreshToken::createEmpty();
            $refreshToken = $refreshToken->create(
                RefreshTokenId::create('REFRESH_TOKEN_ID'),
                UserAccountId::create('USER_ACCOUNT_ID'),
                ClientId::create('CLIENT_ID'),
                DataBag::create([
                    'scope' => 'scope1 scope2',
                ]),
                DataBag::create([]),
                new \DateTimeImmutable('now +1hour'),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $revokedRefreshToken = RefreshToken::createEmpty();
            $revokedRefreshToken = $revokedRefreshToken->create(
                RefreshTokenId::create('REVOKED_REFRESH_TOKEN_ID'),
                UserAccountId::create('USER_ACCOUNT_ID'),
                ClientId::create('CLIENT_ID'),
                DataBag::create([
                    'scope' => 'scope1 scope2',
                ]),
                DataBag::create([]),
                new \DateTimeImmutable('now +1hour'),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $revokedRefreshToken = $revokedRefreshToken->markAsRevoked();
            $expiredRefreshToken = RefreshToken::createEmpty();
            $expiredRefreshToken = $expiredRefreshToken->create(
                RefreshTokenId::create('EXPIRED_REFRESH_TOKEN_ID'),
                UserAccountId::create('USER_ACCOUNT_ID'),
                ClientId::create('CLIENT_ID'),
                DataBag::create([
                    'scope' => 'scope1 scope2',
                ]),
                DataBag::create([]),
                new \DateTimeImmutable('now -1hour'),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $refreshTokenRepository = $this->prophesize(RefreshTokenRepository::class);
            $refreshTokenRepository->save(Argument::type(RefreshToken::class))->will(function () {
            });
            $refreshTokenRepository->find(RefreshTokenId::create('REFRESH_TOKEN_ID'))->willReturn($refreshToken);
            $refreshTokenRepository->find(RefreshTokenId::create('EXPIRED_REFRESH_TOKEN_ID'))->willReturn($expiredRefreshToken);
            $refreshTokenRepository->find(RefreshTokenId::create('REVOKED_REFRESH_TOKEN_ID'))->willReturn($revokedRefreshToken);
            $refreshTokenRepository->find(RefreshTokenId::create('UNKNOWN_TOKEN_ID'))->willReturn(null);
            $this->refreshTokenTypeHint = new RefreshTokenTypeHint(
                $refreshTokenRepository->reveal()
            );
        }

        return $this->refreshTokenTypeHint;
    }
}
