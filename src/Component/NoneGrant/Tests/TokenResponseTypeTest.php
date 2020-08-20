<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\NoneGrant\Tests;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\NoneGrant\AuthorizationStorage;
use OAuth2Framework\Component\NoneGrant\NoneResponseType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @group ResponseType
 * @group None
 *
 * @internal
 */
final class TokenResponseTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function genericInformation()
    {
        $authorizationStorage = $this->prophesize(AuthorizationStorage::class);
        $responseType = new NoneResponseType($authorizationStorage->reveal());

        static::assertEquals([], $responseType->associatedGrantTypes());
        static::assertEquals('none', $responseType->name());
        static::assertEquals('query', $responseType->getResponseMode());
    }

    /**
     * @test
     */
    public function theAuthorizationIsSaved()
    {
        $authorizationStorage = $this->prophesize(AuthorizationStorage::class);
        $authorizationStorage->save(Argument::type(AuthorizationRequest::class))->shouldBeCalled();
        $responseType = new NoneResponseType($authorizationStorage->reveal());

        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $tokenType = $this->prophesize(TokenType::class);
        $tokenType->getAdditionalInformation()->willReturn(['token_type' => 'FOO']);

        $authorization = new AuthorizationRequest($client->reveal(), []);

        $responseType->process($authorization, $tokenType->reveal());

        static::assertEquals('CLIENT_ID', $authorization->getClient()->getPublicId()->getValue());
        static::assertFalse($authorization->hasResponseParameter('access_token'));
    }
}
