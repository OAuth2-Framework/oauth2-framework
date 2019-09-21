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

namespace OAuth2Framework\Component\ImplicitGrant\Tests;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\ImplicitGrant\ImplicitGrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group GrantType
 * @group Implicit
 *
 * @internal
 */
final class ImplicitGrantTypeTest extends TestCase
{
    /**
     * @var null|ImplicitGrantType
     */
    private $grantType;

    /**
     * @test
     */
    public function genericInformation()
    {
        static::assertEquals(['token'], $this->getGrantType()->associatedResponseTypes());
        static::assertEquals('implicit', $this->getGrantType()->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters()
    {
        $request = $this->prophesize(ServerRequestInterface::class);

        try {
            $this->getGrantType()->checkRequest($request->reveal());
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
                'error' => 'invalid_grant',
                'error_description' => 'The implicit grant type cannot be called from the token endpoint.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPrepared()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['implicit' => 'REFRESH_TOKEN_ID']);
        $grantTypeData = new GrantTypeData($client->reveal());

        try {
            $this->getGrantType()->prepareResponse($request->reveal(), $grantTypeData);
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
                'error' => 'invalid_grant',
                'error_description' => 'The implicit grant type cannot be called from the token endpoint.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClient()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['implicit' => 'REFRESH_TOKEN_ID']);
        $request->getAttribute('client')->willReturn($client);
        $grantTypeData = new GrantTypeData($client->reveal());

        try {
            $this->getGrantType()->grant($request->reveal(), $grantTypeData);
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
                'error' => 'invalid_grant',
                'error_description' => 'The implicit grant type cannot be called from the token endpoint.',
            ], $e->getData());
        }
    }

    private function getGrantType(): ImplicitGrantType
    {
        if (null === $this->grantType) {
            $this->grantType = new ImplicitGrantType();
        }

        return $this->grantType;
    }
}
