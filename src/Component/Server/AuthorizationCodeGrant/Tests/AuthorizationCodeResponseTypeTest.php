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

namespace OAuth2Framework\Component\Server\AuthorizationCodeResponse\Tests;

use OAuth2Framework\Component\Server\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\PKCEMethod\Plain;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\PKCEMethod\S256;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\AuthorizationCode;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
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
        self::assertEquals(['authorization_code'], $this->getResponseType()->getAssociatedGrantTypes());
        self::assertEquals('code', $this->getResponseType()->getResponseType());
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
        $authorization = Authorization::create(
            $client,
            [
                'code_challenge' => 'ABCDEFGH',
                'code_challenge_method' => 'S256',
            ]
        );
        $authorization = $authorization->withUserAccount($userAccount->reveal(), true);
        $authorization = $authorization->withRedirectUri('http://localhost:8000/');
        $authorization = $this->getResponseType()->process($authorization, function (Authorization $authorization) {
            return $authorization;
        });
        self::assertTrue($authorization->hasResponseParameter('code'));
    }

    /**
     * @var AuthorizationCodeResponseType|null
     */
    private $grantType = null;

    private function getResponseType(): AuthorizationCodeResponseType
    {
        if (null === $this->grantType) {
            $authorizationCodeRepository = $this->prophesize(AuthorizationCodeRepository::class);
            $authorizationCodeRepository->save(Argument::type(AuthorizationCode::class))->willReturn(null);

            $this->grantType = new AuthorizationCodeResponseType(
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
