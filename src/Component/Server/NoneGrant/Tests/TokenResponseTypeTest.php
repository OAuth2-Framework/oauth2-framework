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

namespace OAuth2Framework\Component\Server\NoneGrant\Tests;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\NoneGrant\AuthorizationStorage;
use OAuth2Framework\Component\Server\NoneGrant\NoneResponseType;
use OAuth2Framework\Component\Server\TokenType\TokenType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group ResponseType
 * @group None
 */
final class TokenResponseTypeTest extends TestCase
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
        $tokenType->getInformation()->willReturn(['token_type' => 'FOO']);

        $authorization = Authorization::create($client, []);
        $authorization = $authorization->withResponseTypes([$responseType]);

        $authorization = $responseType->process($authorization, function (Authorization $authorization) {
            return $authorization;
        });

        self::assertEquals('CLIENT_ID', $authorization->getClient()->getPublicId()->getValue());
        self::assertFalse($authorization->hasResponseParameter('access_token'));
    }

    /**
     * @test
     */
    public function theNoneResponseTypeMustBeUsedAlone()
    {
        $authorizationStorage = $this->prophesize(AuthorizationStorage::class);
        $authorizationStorage->save(Argument::type(Authorization::class))->shouldNotBeCalled();
        $responseType = new NoneResponseType($authorizationStorage->reveal());
        $anotherResponseType = $this->prophesize(ResponseType::class);

        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();
        $tokenType = $this->prophesize(TokenType::class);
        $tokenType->getInformation()->willReturn(['token_type' => 'FOO']);

        $authorization = Authorization::create($client, []);
        $authorization = $authorization->withResponseTypes([$responseType, $anotherResponseType->reveal()]);

        try {
            $responseType->process($authorization, function (Authorization $authorization) {
                return $authorization;
            });
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'The response type "none" cannot be used with another response type.',
            ], $e->getData());
        }
    }
}
