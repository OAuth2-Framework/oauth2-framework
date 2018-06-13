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

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\Plain;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\S256;
use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCode;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeIdGenerator;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group ResponseType
 * @group AuthorizationCodeResponseType
 */
final class AuthorizationCodeResponseTypeTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertEquals(['authorization_code'], $this->getResponseType()->associatedGrantTypes());
        self::assertEquals('code', $this->getResponseType()->name());
        self::assertEquals('query', $this->getResponseType()->getResponseMode());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getPublicId()->willReturn(UserAccountId::create('USER_ACCOUNT_ID'));
        $userAccount->getUserAccountId()->willReturn(UserAccountId::create('USER_ACCOUNT_ID'));
        $authorization = Authorization::create(
            $client,
            [
                'code_challenge' => 'ABCDEFGH',
                'code_challenge_method' => 'S256',
            ]
        );
        $authorization = $authorization->withUserAccount($userAccount->reveal(), true);
        $authorization = $authorization->withRedirectUri('http://localhost:8000/');
        $authorization = $this->getResponseType()->preProcess($authorization);
        $authorization = $this->getResponseType()->process($authorization);
        self::assertTrue($authorization->hasResponseParameter('code'));
    }

    /**
     * @var AuthorizationCodeResponseType|null
     */
    private $grantType = null;

    private function getResponseType(): AuthorizationCodeResponseType
    {
        if (null === $this->grantType) {
            $authorizationCodeIdGenerator = $this->prophesize(AuthorizationCodeIdGenerator::class);
            $authorizationCodeIdGenerator->createAuthorizationCodeId()->willReturn(
                AuthorizationCodeId::create(bin2hex(random_bytes(32)))
            );
            $authorizationCodeRepository = $this->prophesize(AuthorizationCodeRepository::class);
            $authorizationCodeRepository->save(Argument::type(AuthorizationCode::class))->will(function (array $args) {
            });

            $this->grantType = new AuthorizationCodeResponseType(
                $authorizationCodeIdGenerator->reveal(),
                $authorizationCodeRepository->reveal(),
                30,
                $this->getPkceMethodManager(),
                false
            );
        }

        return $this->grantType;
    }

    /**
     * @var null|PKCEMethodManager
     */
    private $pkceMethodManager = null;

    private function getPkceMethodManager(): PKCEMethodManager
    {
        if (null === $this->pkceMethodManager) {
            $this->pkceMethodManager = new PKCEMethodManager();
            $this->pkceMethodManager->add(new Plain());
            $this->pkceMethodManager->add(new S256());
        }

        return $this->pkceMethodManager;
    }
}
