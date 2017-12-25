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

namespace OAuth2Framework\Component\Server\ClientCredentialsGrant\Tests;

use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\ClientCredentialsGrant\ClientCredentialsGrantType;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group GrantType
 * @group ClientCredentials
 */
final class ClientCredentialsGrantTypeTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertTrue($this->getGrantType()->isRefreshTokenIssuedWithAccessToken());
        self::assertEquals([], $this->getGrantType()->getAssociatedResponseTypes());
        self::assertEquals('client_credentials', $this->getGrantType()->getGrantType());
    }

    /**
     * @test
     */
    public function theRequestHaveAllRequiredParameters()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $this->getGrantType()->checkTokenRequest($request->reveal());
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPrepared()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $request = $this->prophesize(ServerRequestInterface::class);
        $grantTypeData = GrantTypeData::create($client);

        $receivedGrantTypeData = $this->getGrantType()->prepareTokenResponse($request->reveal(), $grantTypeData);
        self::assertSame($receivedGrantTypeData, $grantTypeData);
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClient()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();
        $request = $this->prophesize(ServerRequestInterface::class);
        $grantTypeData = GrantTypeData::create($client);

        $receivedGrantTypeData = $this->getGrantType()->grant($request->reveal(), $grantTypeData);
        self::assertNotSame($receivedGrantTypeData, $grantTypeData);
        self::assertEquals('CLIENT_ID', $receivedGrantTypeData->getResourceOwnerId()->getValue());
        self::assertEquals('CLIENT_ID', $receivedGrantTypeData->getClient()->getPublicId()->getValue());
    }

    /**
     * @var ClientCredentialsGrantType|null
     */
    private $grantType = null;

    private function getGrantType(): ClientCredentialsGrantType
    {
        if (null === $this->grantType) {
            $this->grantType = new ClientCredentialsGrantType(true);
        }

        return $this->grantType;
    }
}
