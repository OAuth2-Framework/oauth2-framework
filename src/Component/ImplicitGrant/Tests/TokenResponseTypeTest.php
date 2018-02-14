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

namespace OAuth2Framework\Component\ImplicitGrant\Tests;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenIdGenerator;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;
use OAuth2Framework\Component\TokenType\TokenType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group ResponseType
 * @group Token
 */
class TokenResponseTypeTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertEquals(['implicit'], $this->getResponseType()->associatedGrantTypes());
        self::assertEquals('token', $this->getResponseType()->name());
        self::assertEquals('fragment', $this->getResponseType()->getResponseMode());
    }

    /**
     * @test
     */
    public function anAccessTokenIsCreatedDuringTheAuthorizationProcess()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();
        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getPublicId()->willReturn(UserAccountId::create('USER_ACCOUNT_ID'));
        $tokenType = $this->prophesize(TokenType::class);
        $tokenType->getAdditionalInformation()->willReturn(['token_type' => 'FOO']);

        $authorization = Authorization::create(
            $client,
            []
        );
        $authorization = $authorization->withUserAccount($userAccount->reveal(), true);
        $authorization = $authorization->withTokenType($tokenType->reveal());

        $authorization = $this->getResponseType()->process($authorization, function (Authorization $authorization) {
            return $authorization;
        });

        self::assertEquals('CLIENT_ID', $authorization->getClient()->getPublicId()->getValue());
        self::assertTrue($authorization->hasResponseParameter('access_token'));
        self::assertEquals('ACCESS_TOKEN_ID', $authorization->getResponseParameter('access_token'));
    }

    /**
     * @var TokenResponseType|null
     */
    private $grantType = null;

    private function getResponseType(): TokenResponseType
    {
        if (null === $this->grantType) {
            $accessTokenIdGenerator = $this->prophesize(AccessTokenIdGenerator::class);
            $accessTokenIdGenerator->create(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->willReturn(AccessTokenId::create('ACCESS_TOKEN_ID'));

            $accessTokenRepository = $this->prophesize(AccessTokenRepository::class);
            $accessTokenRepository->save(Argument::type(AccessToken::class))->willReturn(null);

            $this->grantType = new TokenResponseType(
                $accessTokenRepository->reveal(),
                $accessTokenIdGenerator->reveal(),
                3600
            );
        }

        return $this->grantType;
    }
}
