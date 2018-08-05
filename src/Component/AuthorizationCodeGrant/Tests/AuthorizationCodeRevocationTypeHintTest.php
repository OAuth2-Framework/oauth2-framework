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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\Tests;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCode;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRevocationTypeHint;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHint;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group TypeHint
 * @group AuthorizationCodeRevocationTypeHint
 */
final class AuthorizationCodeRevocationTypeHintTest extends TestCase
{
    protected function setUp()
    {
        if (!\interface_exists(TokenTypeHint::class)) {
            static::markTestSkipped('The component "oauth2-framework/token-revocation-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function genericInformation()
    {
        static::assertEquals('auth_code', $this->getAuthorizationCodeRevocationTypeHint()->hint());
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndRevokeIt()
    {
        static::assertNull($this->getAuthorizationCodeRevocationTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $authorizationCode = $this->getAuthorizationCodeRevocationTypeHint()->find('AUTHORIZATION_CODE_ID');
        static::assertInstanceOf(AuthorizationCode::class, $authorizationCode);
        $this->getAuthorizationCodeRevocationTypeHint()->revoke($authorizationCode);
        static::assertTrue(true);
    }

    /**
     * @var null|AuthorizationCodeRevocationTypeHint
     */
    private $authorizationCodeRevocationTypeHint = null;

    public function getAuthorizationCodeRevocationTypeHint(): AuthorizationCodeRevocationTypeHint
    {
        if (null === $this->authorizationCodeRevocationTypeHint) {
            $authorizationCode = new AuthorizationCode(
                new AuthorizationCodeId('AUTHORIZATION_CODE_ID'),
                new ClientId('CLIENT_ID'),
                new UserAccountId('USER_ACCOUNT_ID'),
                [],
                'http://localhost:8000',
                new \DateTimeImmutable('now +1hour'),
                new DataBag([
                    'scope' => 'scope1 scope2',
                ]),
                new DataBag([]),
                new ResourceServerId('RESOURCE_SERVER_ID')
            );
            $authorizationCodeRepository = $this->prophesize(AuthorizationCodeRepository::class);
            $authorizationCodeRepository->find(Argument::type(AuthorizationCodeId::class))->will(function ($args) use ($authorizationCode) {
                if ('AUTHORIZATION_CODE_ID' === $args[0]->getValue()) {
                    return $authorizationCode;
                }

                return;
            });
            $authorizationCodeRepository->save(Argument::type(AuthorizationCode::class))->will(function () {
            });
            $this->authorizationCodeRevocationTypeHint = new AuthorizationCodeRevocationTypeHint(
                $authorizationCodeRepository->reveal()
            );
        }

        return $this->authorizationCodeRevocationTypeHint;
    }
}
