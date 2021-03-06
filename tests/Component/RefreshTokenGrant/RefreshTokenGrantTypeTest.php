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

namespace OAuth2Framework\Tests\Component\RefreshTokenGrant;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenGrantType;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @group GrantType
 * @group RefreshToken
 *
 * @internal
 */
final class RefreshTokenGrantTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var null|RefreshTokenGrantType
     */
    private $grantType;

    /**
     * @test
     */
    public function genericInformation()
    {
        static::assertEquals([], $this->getGrantType()->associatedResponseTypes());
        static::assertEquals('refresh_token', $this->getGrantType()->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters()
    {
        $request = $this->buildRequest(['password' => 'PASSWORD']);

        try {
            $this->getGrantType()->checkRequest($request->reveal());
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
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
        $request = $this->buildRequest(['refresh_token' => 'REFRESH_TOKEN_ID']);

        $this->getGrantType()->checkRequest($request->reveal());
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function theRefreshTokenDoesNotExist()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $grantTypeData = new GrantTypeData($client->reveal());
        $request = $this->buildRequest(['refresh_token' => 'UNKNOWN_REFRESH_TOKEN_ID']);

        try {
            $this->getGrantType()->grant($request->reveal(), $grantTypeData);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
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
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $request = $this->buildRequest(['refresh_token' => 'REFRESH_TOKEN_ID']);
        $grantTypeData = new GrantTypeData($client->reveal());

        $this->getGrantType()->prepareResponse($request->reveal(), $grantTypeData);
        static::assertSame($grantTypeData, $grantTypeData);
    }

    /**
     * @test
     */
    public function theRefreshTokenIsRevoked()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $request = $this->buildRequest(['refresh_token' => 'REVOKED_REFRESH_TOKEN_ID']);
        $request->getAttribute('client')->willReturn($client);
        $grantTypeData = new GrantTypeData($client->reveal());

        try {
            $this->getGrantType()->grant($request->reveal(), $grantTypeData);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
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
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('OTHER_CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('OTHER_CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $request = $this->buildRequest(['refresh_token' => 'REFRESH_TOKEN_ID']);
        $request->getAttribute('client')->willReturn($client->reveal());
        $grantTypeData = new GrantTypeData($client->reveal());

        try {
            $this->getGrantType()->grant($request->reveal(), $grantTypeData);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
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
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $request = $this->buildRequest(['refresh_token' => 'EXPIRED_REFRESH_TOKEN_ID']);
        $request->getAttribute('client')->willReturn($client);
        $grantTypeData = new GrantTypeData($client->reveal());

        try {
            $this->getGrantType()->grant($request->reveal(), $grantTypeData);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
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
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $request = $this->buildRequest(['refresh_token' => 'REFRESH_TOKEN_ID']);
        $request->getAttribute('client')->willReturn($client);
        $grantTypeData = new GrantTypeData($client->reveal());

        $this->getGrantType()->grant($request->reveal(), $grantTypeData);
        static::assertEquals('CLIENT_ID', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertEquals('CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }

    private function getGrantType(): RefreshTokenGrantType
    {
        if (null === $this->grantType) {
            $refreshToken = new RefreshToken(
                new RefreshTokenId('REFRESH_TOKEN_ID'),
                new ClientId('CLIENT_ID'),
                new ClientId('CLIENT_ID'),
                new \DateTimeImmutable('now +1 day'),
                new DataBag([
                    'metadata' => 'foo',
                    'scope' => 'scope1 scope2',
                ]),
                new DataBag([
                    'parameter1' => 'bar', ]),
                new ResourceServerId('RESOURCE_SERVER_ID')
            );

            $revokedRefreshToken = new RefreshToken(
                new RefreshTokenId('REVOKED_REFRESH_TOKEN_ID'),
                new ClientId('CLIENT_ID'),
                new ClientId('CLIENT_ID'),
                new \DateTimeImmutable('now +1 day'),
                new DataBag([
                    'metadata' => 'foo',
                    'scope' => 'scope1 scope2',
                ]),
                new DataBag([
                    'parameter1' => 'bar', ]),
                new ResourceServerId('RESOURCE_SERVER_ID')
            );
            $revokedRefreshToken->markAsRevoked();

            $expiredRefreshToken = new RefreshToken(
                new RefreshTokenId('EXPIRED_REFRESH_TOKEN_ID'),
                new ClientId('CLIENT_ID'),
                new ClientId('CLIENT_ID'),
                new \DateTimeImmutable('now -1 day'),
                new DataBag([
                    'metadata' => 'foo',
                    'scope' => 'scope1 scope2',
                ]),
                new DataBag([
                    'parameter1' => 'bar', ]),
                new ResourceServerId('RESOURCE_SERVER_ID')
            );

            $refreshTokenRepository = $this->prophesize(RefreshTokenRepository::class);
            $refreshTokenRepository->find(new RefreshTokenId('REFRESH_TOKEN_ID'))->willReturn($refreshToken);
            $refreshTokenRepository->find(new RefreshTokenId('UNKNOWN_REFRESH_TOKEN_ID'))->willReturn(null);
            $refreshTokenRepository->find(new RefreshTokenId('REVOKED_REFRESH_TOKEN_ID'))->willReturn($revokedRefreshToken);
            $refreshTokenRepository->find(new RefreshTokenId('EXPIRED_REFRESH_TOKEN_ID'))->willReturn($expiredRefreshToken);

            $this->grantType = new RefreshTokenGrantType(
                $refreshTokenRepository->reveal()
            );
        }

        return $this->grantType;
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()->willReturn(http_build_query($data));
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')->willReturn(true);
        $request->getHeader('Content-Type')->willReturn(['application/x-www-form-urlencoded']);
        $request->getBody()->willReturn($body->reveal());
        $request->getParsedBody()->willReturn([]);

        return $request;
    }
}
