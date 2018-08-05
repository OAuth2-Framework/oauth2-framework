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

namespace OAuth2Framework\Component\Core\Tests\AccessToken;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRevocationTypeHint;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHint;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group TypeHint
 * @group AccessTokenRevocationTypeHint
 */
final class AccessTokenRevocationTypeHintTest extends TestCase
{
    protected function setUp()
    {
        if (!\interface_exists(TokenTypeHint::class)) {
            static::markTestSkipped('The component "oauth2-framework/token-type" is not installed.');
        }
    }

    /**
     * @test
     */
    public function genericInformation()
    {
        static::assertEquals('access_token', $this->getAccessTokenRevocationTypeHint()->hint());
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndRevokeIt()
    {
        static::assertNull($this->getAccessTokenRevocationTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $accessToken = $this->getAccessTokenRevocationTypeHint()->find('ACCESS_TOKEN_ID');
        static::assertInstanceOf(AccessToken::class, $accessToken);
        $this->getAccessTokenRevocationTypeHint()->revoke($accessToken);
        static::assertTrue(true);
    }

    /**
     * @var null|AccessTokenRevocationTypeHint
     */
    private $accessTokenTypeHint = null;

    public function getAccessTokenRevocationTypeHint(): AccessTokenRevocationTypeHint
    {
        if (null === $this->accessTokenTypeHint) {
            $accessToken = new AccessToken(
                new AccessTokenId('ACCESS_TOKEN_ID'),
                new ClientId('CLIENT_ID'),
                new UserAccountId('USER_ACCOUNT_ID'),
                new \DateTimeImmutable('now +1hour'),
                new DataBag([
                    'scope' => 'scope1 scope2',
                ]),
                new DataBag([]),
                new ResourceServerId('RESOURCE_SERVER_ID')
            );
            $accessTokenRepository = $this->prophesize(AccessTokenRepository::class);
            $accessTokenRepository->find(Argument::type(AccessTokenId::class))->will(function ($args) use ($accessToken) {
                if ('ACCESS_TOKEN_ID' === $args[0]->getValue()) {
                    return $accessToken;
                }

                return;
            });
            $accessTokenRepository->save(Argument::type(AccessToken::class))->will(function () {
            });

            $this->accessTokenTypeHint = new AccessTokenRevocationTypeHint(
                $accessTokenRepository->reveal()
            );
        }

        return $this->accessTokenTypeHint;
    }
}
