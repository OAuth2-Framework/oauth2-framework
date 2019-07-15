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

namespace OAuth2Framework\Component\ClientCredentialsGrant\Tests;

use OAuth2Framework\Component\ClientCredentialsGrant\ClientCredentialsGrantType;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group GrantType
 * @group ClientCredentials
 *
 * @internal
 * @coversNothing
 */
final class ClientCredentialsGrantTypeTest extends TestCase
{
    /**
     * @var null|ClientCredentialsGrantType
     */
    private $grantType;

    /**
     * @test
     */
    public function genericInformation()
    {
        static::assertEquals([], $this->getGrantType()->associatedResponseTypes());
        static::assertEquals('client_credentials', $this->getGrantType()->name());
    }

    /**
     * @test
     */
    public function theRequestHaveAllRequiredParameters()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $this->getGrantType()->checkRequest($request->reveal());
        static::assertTrue(true);
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
        $grantTypeData = new GrantTypeData($client->reveal());

        $this->getGrantType()->prepareResponse($request->reveal(), $grantTypeData);
        static::assertSame($grantTypeData, $grantTypeData);
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
        $grantTypeData = new GrantTypeData($client->reveal());

        $this->getGrantType()->grant($request->reveal(), $grantTypeData);
        static::assertEquals('CLIENT_ID', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertEquals('CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }

    private function getGrantType(): ClientCredentialsGrantType
    {
        if (null === $this->grantType) {
            $this->grantType = new ClientCredentialsGrantType();
        }

        return $this->grantType;
    }
}
