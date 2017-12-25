<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\RefreshTokenGrant\Tests;

use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenRepository;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenTypeHint;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group TypeHint
 * @group RefreshTokenTypeHint
 */
final class RefreshTokenTypeHintTest extends TestCase
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
        self::assertInstanceOf(RefreshToken::class, $refreshToken);
        $introspection = $this->getRefreshTokenTypeHint()->introspect($refreshToken);
        self::arrayHasKey('active', $introspection);
        self::assertTrue($introspection['active']);
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndRevokeIt()
    {
        self::assertNull($this->getRefreshTokenTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $refreshToken = $this->getRefreshTokenTypeHint()->find('REFRESH_TOKEN_ID');
        self::assertInstanceOf(RefreshToken::class, $refreshToken);
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
                DataBag::create([]),
                DataBag::create([]),
                ['scope1', 'scope2'],
                new \DateTimeImmutable('now +1hour'),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $refreshTokenRepository = $this->prophesize(RefreshTokenRepository::class);
            $refreshTokenRepository->save(Argument::type(RefreshToken::class))->will(function(){});
            $refreshTokenRepository->find(Argument::type(RefreshTokenId::class))->will(function ($args) use ($refreshToken) {
                if ($args[0]->getValue() === 'REFRESH_TOKEN_ID') {
                    return $refreshToken;
                }

                return null;
            });
            $this->refreshTokenTypeHint = new RefreshTokenTypeHint(
                $refreshTokenRepository->reveal()
            );
        }

        return $this->refreshTokenTypeHint;
    }
}
