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

namespace OAuth2Framework\Component\RefreshTokenGrant\Tests;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenGrantType;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group GrantType
 * @group RefreshToken
 */
class RefreshTokenGrantTypeTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertEquals([], $this->getGrantType()->associatedResponseTypes());
        self::assertEquals('refresh_token', $this->getGrantType()->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['password' => 'PASSWORD']);

        try {
            $this->getGrantType()->checkRequest($request->reveal());
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

        $this->getGrantType()->checkRequest($request->reveal());
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function theRefreshTokenDoesNotExist()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $grantTypeData = GrantTypeData::create($client);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['refresh_token' => 'UNKNOWN_REFRESH_TOKEN_ID']);

        try {
            $this->getGrantType()->grant($request->reveal(), $grantTypeData);
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_grant',
                'error_description' => 'The parameter "refresh_token" is invalid.',
            ], $e->getData());
        }
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

        $receivedGrantTypeData = $this->getGrantType()->prepareResponse($request->reveal(), $grantTypeData);
        self::assertSame($receivedGrantTypeData, $grantTypeData);
    }

    /**
     * @test
     */
    public function theRefreshTokenIsRevoked()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['refresh_token' => 'REVOKED_REFRESH_TOKEN_ID']);
        $request->getAttribute('client')->willReturn($client);
        $grantTypeData = GrantTypeData::create($client);

        try {
            $this->getGrantType()->grant($request->reveal(), $grantTypeData);
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_grant',
                'error_description' => 'The parameter "refresh_token" is invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRefreshTokenIsNotForThatClient()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('OTHER_CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['refresh_token' => 'REFRESH_TOKEN_ID']);
        $request->getAttribute('client')->willReturn($client);
        $grantTypeData = GrantTypeData::create($client);

        try {
            $this->getGrantType()->grant($request->reveal(), $grantTypeData);
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_grant',
                'error_description' => 'The parameter "refresh_token" is invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRefreshTokenExpired()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['refresh_token' => 'EXPIRED_REFRESH_TOKEN_ID']);
        $request->getAttribute('client')->willReturn($client);
        $grantTypeData = GrantTypeData::create($client);

        try {
            $this->getGrantType()->grant($request->reveal(), $grantTypeData);
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_grant',
                'error_description' => 'The refresh token expired.',
            ], $e->getData());
        }
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
        $request->getAttribute('client')->willReturn($client);
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
                DataBag::create([
                    'metadata' => 'foo',
                    'scope' => 'scope1 scope2',
                ]),
                DataBag::create([
                    'parameter1' => 'bar', ]),
                new \DateTimeImmutable('now +1 day'),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $refreshToken->eraseMessages();

            $revokedRefreshToken = RefreshToken::createEmpty();
            $revokedRefreshToken = $revokedRefreshToken->create(
                RefreshTokenId::create('REVOKED_REFRESH_TOKEN_ID'),
                ClientId::create('CLIENT_ID'),
                ClientId::create('CLIENT_ID'),
                DataBag::create([
                    'metadata' => 'foo',
                    'scope' => 'scope1 scope2',
                ]),
                DataBag::create([
                    'parameter1' => 'bar', ]),
                new \DateTimeImmutable('now +1 day'),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $revokedRefreshToken = $revokedRefreshToken->markAsRevoked();
            $revokedRefreshToken->eraseMessages();

            $expiredRefreshToken = RefreshToken::createEmpty();
            $expiredRefreshToken = $expiredRefreshToken->create(
                RefreshTokenId::create('EXPIRED_REFRESH_TOKEN_ID'),
                ClientId::create('CLIENT_ID'),
                ClientId::create('CLIENT_ID'),
                DataBag::create([
                    'metadata' => 'foo',
                    'scope' => 'scope1 scope2',
                ]),
                DataBag::create([
                    'parameter1' => 'bar', ]),
                new \DateTimeImmutable('now -1 day'),
                ResourceServerId::create('RESOURCE_SERVER_ID')
            );
            $expiredRefreshToken->eraseMessages();

            $refreshTokenRepository = $this->prophesize(RefreshTokenRepository::class);
            $refreshTokenRepository->find(RefreshTokenId::create('REFRESH_TOKEN_ID'))->willReturn($refreshToken);
            $refreshTokenRepository->find(RefreshTokenId::create('UNKNOWN_REFRESH_TOKEN_ID'))->willReturn(null);
            $refreshTokenRepository->find(RefreshTokenId::create('REVOKED_REFRESH_TOKEN_ID'))->willReturn($revokedRefreshToken);
            $refreshTokenRepository->find(RefreshTokenId::create('EXPIRED_REFRESH_TOKEN_ID'))->willReturn($expiredRefreshToken);

            $this->grantType = new RefreshTokenGrantType(
                $refreshTokenRepository->reveal()
            );
        }

        return $this->grantType;
    }
}
