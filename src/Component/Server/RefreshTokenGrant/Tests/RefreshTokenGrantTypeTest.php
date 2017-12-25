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

namespace OAuth2Framework\Component\Server\RefreshTokenGrant\Tests;

use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenGrantType;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenRepository;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group GrantType
 * @group RefreshToken
 */
final class RefreshTokenGrantTypeTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertEquals([], $this->getGrantType()->getAssociatedResponseTypes());
        self::assertEquals('refresh_token', $this->getGrantType()->getGrantType());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['password' => 'PASSWORD']);

        try {
            $this->getGrantType()->checkTokenRequest($request->reveal());
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'Missing grant type parameter(s): refresh_token.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRequestHaveAllRequiredParameters()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['refresh_token' => 'REFRESH_TOKEN_ID']);

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
        $request->getParsedBody()->willReturn(['refresh_token' => 'REFRESH_TOKEN_ID']);
        $grantTypeData = GrantTypeData::create($client);

        $receivedGrantTypeData = $this->getGrantType()->prepareTokenResponse($request->reveal(), $grantTypeData);
        self::assertNotSame($receivedGrantTypeData, $grantTypeData);
        self::assertEquals(['scope1', 'scope2'], $receivedGrantTypeData->getAvailableScopes());
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
        $request->getParsedBody()->willReturn(['refresh_token' => 'REFRESH_TOKEN_ID']);
        $request->getAttribute("client")->willReturn($client);
        $grantTypeData = GrantTypeData::create($client);

        $receivedGrantTypeData = $this->getGrantType()->grant($request->reveal(), $grantTypeData);
        self::assertNotSame($receivedGrantTypeData, $grantTypeData);
        self::assertEquals('CLIENT_ID', $receivedGrantTypeData->getResourceOwnerId()->getValue());
        self::assertEquals('CLIENT_ID', $receivedGrantTypeData->getClient()->getPublicId()->getValue());
    }

    /**
     * @var RefreshTokenGrantType|null
     */
    private $grantType = null;

    private function getGrantType(): RefreshTokenGrantType
    {
        if (null === $this->grantType) {
            $refreshToken = RefreshToken::createEmpty();
            $refreshToken = $refreshToken->create(
                RefreshTokenId::create('REFRESH_TOKEN_ID'),
                ClientId::create('CLIENT_ID'),
                ClientId::create('CLIENT_ID'),
                DataBag::create([]),
                DataBag::create([]),
                ['scope1', 'scope2'],
                new \DateTimeImmutable('now +1 day'),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $refreshToken->eraseMessages();
            $refreshTokenRepository = $this->prophesize(RefreshTokenRepository::class);
            $refreshTokenRepository->find(RefreshTokenId::create('REFRESH_TOKEN_ID'))->willReturn($refreshToken);

            $this->grantType = new RefreshTokenGrantType(
                $refreshTokenRepository->reveal()
            );
        }

        return $this->grantType;
    }
}
