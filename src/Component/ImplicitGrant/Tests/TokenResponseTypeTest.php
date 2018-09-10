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
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group ResponseType
 * @group Token
 */
final class TokenResponseTypeTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        static::assertEquals(['implicit'], $this->getResponseType()->associatedGrantTypes());
        static::assertEquals('token', $this->getResponseType()->name());
        static::assertEquals('fragment', $this->getResponseType()->getResponseMode());
    }

    /**
     * @test
     */
    public function anAccessTokenIsCreatedDuringTheAuthorizationProcess()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            new UserAccountId('USER_ACCOUNT_ID')
        );
        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getPublicId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $userAccount->getUserAccountId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $tokenType = $this->prophesize(TokenType::class);
        $tokenType->getAdditionalInformation()->willReturn(['token_type' => 'FOO']);

        $authorization = new Authorization(
            $client,
            []
        );
        $authorization->setUserAccount($userAccount->reveal(), true);
        $authorization->setTokenType($tokenType->reveal());

        $authorization = $this->getResponseType()->process($authorization, function (Authorization $authorization) {
            return $authorization;
        });

        static::assertEquals('CLIENT_ID', $authorization->getClient()->getPublicId()->getValue());
        static::assertTrue($authorization->hasResponseParameter('access_token'));
        static::assertEquals('ACCESS_TOKEN_ID', $authorization->getResponseParameter('access_token'));
    }

    /**
     * @var TokenResponseType|null
     */
    private $grantType = null;

    private function getResponseType(): TokenResponseType
    {
        if (null === $this->grantType) {
            $accessTokenIdGenerator = $this->prophesize(AccessTokenIdGenerator::class);
            $accessTokenIdGenerator->createAccessTokenId(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->willReturn(new AccessTokenId('ACCESS_TOKEN_ID'));

            $accessTokenRepository = $this->prophesize(AccessTokenRepository::class);
            $accessTokenRepository->save(Argument::type(AccessToken::class))->will(function (array $args) {
            });

            $this->grantType = new TokenResponseType(
                $accessTokenIdGenerator->reveal(),
                $accessTokenRepository->reveal(),
                3600
            );
        }

        return $this->grantType;
    }
}
