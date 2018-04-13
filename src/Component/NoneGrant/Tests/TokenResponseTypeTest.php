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

namespace OAuth2Framework\Component\NoneGrant\Tests;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\NoneGrant\AuthorizationStorage;
use OAuth2Framework\Component\NoneGrant\NoneResponseType;
use OAuth2Framework\Component\TokenType\TokenType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group ResponseType
 * @group None
 */
class TokenResponseTypeTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        $authorizationStorage = $this->prophesize(AuthorizationStorage::class);
        $responseType = new NoneResponseType($authorizationStorage->reveal());

        self::assertEquals([], $responseType->associatedGrantTypes());
        self::assertEquals('none', $responseType->name());
        self::assertEquals('query', $responseType->getResponseMode());
    }

    /**
     * @test
     */
    public function theAuthorizationIsSaved()
    {
        $authorizationStorage = $this->prophesize(AuthorizationStorage::class);
        $authorizationStorage->save(Argument::type(Authorization::class))->shouldBeCalled();
        $responseType = new NoneResponseType($authorizationStorage->reveal());

        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();
        $tokenType = $this->prophesize(TokenType::class);
        $tokenType->getAdditionalInformation()->willReturn(['token_type' => 'FOO']);

        $authorization = Authorization::create($client, []);
        $authorization = $authorization->withResponseType($responseType);

        $authorization = $responseType->process($authorization);

        self::assertEquals('CLIENT_ID', $authorization->getClient()->getPublicId()->getValue());
        self::assertFalse($authorization->hasResponseParameter('access_token'));
    }
}
