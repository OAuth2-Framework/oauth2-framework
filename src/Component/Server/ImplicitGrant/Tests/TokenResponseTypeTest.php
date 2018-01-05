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

namespace OAuth2Framework\Component\Server\ImplicitGrant\Tests;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\ImplicitGrant\ImplicitGrantType;
use OAuth2Framework\Component\Server\TokenType\TokenType;
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
        self::assertEquals(['implicit'], $this->getGrantType()->getAssociatedGrantTypes());
        self::assertEquals('token', $this->getGrantType()->getResponseType());
        self::assertEquals('fragment', $this->getGrantType()->getResponseMode());
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
        $tokenType->getInformation()->willReturn(['token_type' => 'FOO']);

        $authorization = Authorization::create(
            $client,
            []
        );
        $authorization = $authorization->withUserAccount($userAccount->reveal(), true);
        $authorization = $authorization->withTokenType($tokenType->reveal());

        $authorization = $this->getGrantType()->process($authorization, function (Authorization $authorization) {
            return $authorization;
        });

        self::assertEquals('CLIENT_ID', $authorization->getClient()->getPublicId()->getValue());
        self::assertTrue($authorization->hasResponseParameter('access_token'));
        self::assertEquals('ACCESS_TOKEN_ID', $authorization->getResponseParameter('access_token'));
    }

    /**
     * @var ImplicitGrantType|null
     */
    private $grantType = null;

    private function getGrantType(): ImplicitGrantType
    {
        if (null === $this->grantType) {
            $accessToken = AccessToken::createEmpty();
            $accessToken = $accessToken->create(
                AccessTokenId::create('ACCESS_TOKEN_ID'),
                ClientId::create('CLIENT_ID'),
                ClientId::create('CLIENT_ID'),
                DataBag::create([]),
                DataBag::create([]),
                [],
                new \DateTimeImmutable('now +1 hour'),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $accessToken->eraseMessages();
            $accessTokenRepository = $this->prophesize(AccessTokenRepository::class);
            $accessTokenRepository->create(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->willReturn($accessToken);
            $accessTokenRepository->save(Argument::type(AccessToken::class))->willReturn(null);

            $this->grantType = new ImplicitGrantType(
                $accessTokenRepository->reveal(),
                3600
            );
        }

        return $this->grantType;
    }
}
