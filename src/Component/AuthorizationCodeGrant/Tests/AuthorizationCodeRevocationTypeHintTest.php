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
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!\interface_exists(TokenTypeHint::class)) {
            $this->markTestSkipped('The component "oauth2-framework/token-revocation-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertEquals('auth_code', $this->getAuthorizationCodeRevocationTypeHint()->hint());
    }

    /**
     * @test
     */
    public function theTokenTypeHintCanFindATokenAndRevokeIt()
    {
        self::assertNull($this->getAuthorizationCodeRevocationTypeHint()->find('UNKNOWN_TOKEN_ID'));
        $authorizationCode = $this->getAuthorizationCodeRevocationTypeHint()->find('AUTHORIZATION_CODE_ID');
        self::assertInstanceOf(AuthorizationCode::class, $authorizationCode);
        $this->getAuthorizationCodeRevocationTypeHint()->revoke($authorizationCode);
        self::assertTrue(true);
    }

    /**
     * @var null|AuthorizationCodeRevocationTypeHint
     */
    private $authorizationCodeRevocationTypeHint = null;

    /**
     * @return AuthorizationCodeRevocationTypeHint
     */
    public function getAuthorizationCodeRevocationTypeHint(): AuthorizationCodeRevocationTypeHint
    {
        if (null === $this->authorizationCodeRevocationTypeHint) {
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
            $authorizationCodeRepository->find(Argument::type(AuthorizationCodeId::class))->will(function ($args) use ($authorizationCode) {
                if ('AUTHORIZATION_CODE_ID' === $args[0]->getValue()) {
                    return $authorizationCode;
                }

                return null;
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
