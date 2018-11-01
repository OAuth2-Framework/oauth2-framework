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
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\Plain;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\S256;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
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
        static::assertEquals(['authorization_code'], $this->getResponseType()->associatedGrantTypes());
        static::assertEquals('code', $this->getResponseType()->name());
        static::assertEquals('query', $this->getResponseType()->getResponseMode());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));

        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getPublicId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $userAccount->getUserAccountId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $authorization = new AuthorizationRequest(
            $client->reveal(),
            [
                'code_challenge' => 'ABCDEFGH',
                'code_challenge_method' => 'S256',
            ]
        );
        $authorization->setUserAccount($userAccount->reveal(), true);
        $authorization->setRedirectUri('http://localhost:8000/');
        $this->getResponseType()->preProcess($authorization);
        $this->getResponseType()->process($authorization);
        static::assertTrue($authorization->hasResponseParameter('code'));
    }

    /**
     * @var AuthorizationCodeResponseType|null
     */
    private $grantType = null;

    private function getResponseType(): AuthorizationCodeResponseType
    {
        if (null === $this->grantType) {
            $authorizationCodeRepository = $this->prophesize(AuthorizationCodeRepository::class);
            $authorizationCodeRepository->create(Argument::type(ClientId::class), Argument::type(UserAccountId::class), Argument::type('array'), Argument::type('string'), Argument::type(\DateTimeImmutable::class), Argument::type(DataBag::class), Argument::type(DataBag::class), Argument::any())->will(function (array $args) {
                return new AuthorizationCode(
                    new AuthorizationCodeId(\bin2hex(\random_bytes(32))),
                    $args[0],
                    $args[1],
                    $args[2],
                    $args[3],
                    $args[4],
                    $args[5],
                    $args[6],
                    $args[7]
                );
            });
            $authorizationCodeRepository->save(Argument::type(AuthorizationCode::class))->will(function (array $args) {
            });

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
