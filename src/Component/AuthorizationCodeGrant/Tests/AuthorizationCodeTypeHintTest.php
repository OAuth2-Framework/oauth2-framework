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
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeTypeHint;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group TypeHint
 * @group AuthorizationCodeTypeHint
 */
class AuthorizationCodeTypeHintTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertEquals('auth_code', $this->getAuthorizationCodeTypeHint()->hint());
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndReturnValues()
    {
        self::assertNull($this->getAuthorizationCodeTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $authorizationCode = $this->getAuthorizationCodeTypeHint()->find('AUTHORIZATION_CODE_ID');
        self::assertInstanceOf(AuthorizationCode::class, $authorizationCode);
        $introspection = $this->getAuthorizationCodeTypeHint()->introspect($authorizationCode);
        self::arrayHasKey('active', $introspection);
        self::assertTrue($introspection['active']);
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndRevokeIt()
    {
        self::assertNull($this->getAuthorizationCodeTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $authorizationCode = $this->getAuthorizationCodeTypeHint()->find('AUTHORIZATION_CODE_ID');
        self::assertInstanceOf(AuthorizationCode::class, $authorizationCode);
        $this->getAuthorizationCodeTypeHint()->revoke($authorizationCode);
        self::assertTrue(true);
    }

    /**
     * @var null|AuthorizationCodeTypeHint
     */
    private $authorizationCodeTypeHint = null;

    /**
     * @return AuthorizationCodeTypeHint
     */
    public function getAuthorizationCodeTypeHint(): AuthorizationCodeTypeHint
    {
        if (null === $this->authorizationCodeTypeHint) {
            $authorizationCode = AuthorizationCode::createEmpty();
            $authorizationCode = $authorizationCode->create(
                AuthorizationCodeId::create('AUTHORIZATION_CODE_ID'),
                ClientId::create('CLIENT_ID'),
                UserAccountId::create('USER_ACCOUNT_ID'),
                [],
                'http://localhost:8000',
                new \DateTimeImmutable('now +1hour'),
                DataBag::create([
                    'scope' => 'scope1 scope2',
                ]),
                DataBag::create([]),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $authorizationCodeRepository = $this->prophesize(AuthorizationCodeRepository::class);
            $authorizationCodeRepository->save(Argument::type(AuthorizationCode::class))->will(function () {
            });
            $authorizationCodeRepository->find(Argument::type(AuthorizationCodeId::class))->will(function ($args) use ($authorizationCode) {
                if ('AUTHORIZATION_CODE_ID' === $args[0]->getValue()) {
                    return $authorizationCode;
                }

                return null;
            });
            $this->authorizationCodeTypeHint = new AuthorizationCodeTypeHint(
                $authorizationCodeRepository->reveal()
            );
        }

        return $this->authorizationCodeTypeHint;
    }
}
