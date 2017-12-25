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

namespace OAuth2Framework\Component\Server\Core\Tests\AccessToken;

use OAuth2Framework\Component\Server\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenTypeHint;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group TypeHint
 * @group AccessTokenTypeHint
 */
final class AccessTokenTypeHintTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertEquals('access_token', $this->getAccessTokenTypeHint()->hint());
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndReturnValues()
    {
        self::assertNull($this->getAccessTokenTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $accessToken = $this->getAccessTokenTypeHint()->find('ACCESS_TOKEN_ID');
        self::assertInstanceOf(AccessToken::class, $accessToken);
        $introspection = $this->getAccessTokenTypeHint()->introspect($accessToken);
        self::arrayHasKey('active', $introspection);
        self::assertTrue($introspection['active']);
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndRevokeIt()
    {
        self::assertNull($this->getAccessTokenTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $accessToken = $this->getAccessTokenTypeHint()->find('ACCESS_TOKEN_ID');
        self::assertInstanceOf(AccessToken::class, $accessToken);
        $this->getAccessTokenTypeHint()->revoke($accessToken);
        self::assertTrue(true);
    }

    /**
     * @var null|AccessTokenTypeHint
     */
    private $accessTokenTypeHint = null;

    /**
     * @return AccessTokenTypeHint
     */
    public function getAccessTokenTypeHint(): AccessTokenTypeHint
    {
        if (null === $this->accessTokenTypeHint) {
            $accessToken = AccessToken::createEmpty();
            $accessToken = $accessToken->create(
                AccessTokenId::create('ACCESS_TOKEN_ID'),
                UserAccountId::create('USER_ACCOUNT_ID'),
                ClientId::create('CLIENT_ID'),
                DataBag::create([]),
                DataBag::create([]),
                ['scope1', 'scope2'],
                new \DateTimeImmutable('now +1hour'),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $accessTokenRepository = $this->prophesize(AccessTokenRepository::class);
            $accessTokenRepository->save(Argument::type(AccessToken::class))->will(function(){});
            $accessTokenRepository->find(Argument::type(AccessTokenId::class))->will(function ($args) use ($accessToken) {
                if ($args[0]->getValue() === 'ACCESS_TOKEN_ID') {
                    return $accessToken;
                }

                return null;
            });
            $this->accessTokenTypeHint = new AccessTokenTypeHint(
                $accessTokenRepository->reveal()
            );
        }

        return $this->accessTokenTypeHint;
    }
}
