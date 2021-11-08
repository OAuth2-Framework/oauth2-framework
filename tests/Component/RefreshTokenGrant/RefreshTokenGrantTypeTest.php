<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\RefreshTokenGrant;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class RefreshTokenGrantTypeTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame([], $this->getGrantTypeManager()->get('refresh_token')->associatedResponseTypes());
        static::assertSame('refresh_token', $this->getGrantTypeManager()->get('refresh_token')->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters(): void
    {
        $request = $this->buildRequest('GET', [
            'password' => 'PASSWORD',
        ]);

        try {
            $this->getGrantTypeManager()
                ->get('refresh_token')
                ->checkRequest($request)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Missing grant type parameter(s): refresh_token.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRequestHaveAllRequiredParameters(): void
    {
        $request = $this->buildRequest('GET', [
            'refresh_token' => 'REFRESH_TOKEN_ID',
        ]);

        $this->getGrantTypeManager()
            ->get('refresh_token')
            ->checkRequest($request)
        ;
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function theRefreshTokenDoesNotExist(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create(),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $grantTypeData = GrantTypeData::create($client);
        $request = $this->buildRequest('GET', [
            'refresh_token' => 'UNKNOWN_REFRESH_TOKEN_ID',
        ]);

        try {
            $this->getGrantTypeManager()
                ->get('refresh_token')
                ->grant($request, $grantTypeData)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_grant',
                'error_description' => 'The parameter "refresh_token" is invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPrepared(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create(),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'refresh_token' => 'REFRESH_TOKEN_ID',
        ]);
        $grantTypeData = GrantTypeData::create($client);

        $this->getGrantTypeManager()
            ->get('refresh_token')
            ->prepareResponse($request, $grantTypeData)
        ;
        static::assertSame($grantTypeData, $grantTypeData);
    }

    /**
     * @test
     */
    public function theRefreshTokenIsRevoked(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create(),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'refresh_token' => 'REVOKED_REFRESH_TOKEN_ID',
        ]);
        $request = $request->withAttribute('client', $client);

        $grantTypeData = GrantTypeData::create($client);

        try {
            $this->getGrantTypeManager()
                ->get('refresh_token')
                ->grant($request, $grantTypeData)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_grant',
                'error_description' => 'The parameter "refresh_token" is invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRefreshTokenIsNotForThatClient(): void
    {
        $client = Client::create(
            ClientId::create('OTHER_CLIENT_ID'),
            DataBag::create(),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'refresh_token' => 'REFRESH_TOKEN_ID',
        ]);
        $request = $request->withAttribute('client', $client);

        $grantTypeData = GrantTypeData::create($client);

        try {
            $this->getGrantTypeManager()
                ->get('refresh_token')
                ->grant($request, $grantTypeData)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_grant',
                'error_description' => 'The parameter "refresh_token" is invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRefreshTokenExpired(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create(),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'refresh_token' => 'EXPIRED_REFRESH_TOKEN_ID',
        ]);
        $request = $request->withAttribute('client', $client);

        $grantTypeData = GrantTypeData::create($client);

        try {
            $this->getGrantTypeManager()
                ->get('refresh_token')
                ->grant($request, $grantTypeData)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_grant',
                'error_description' => 'The refresh token expired.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClient(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create(),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'refresh_token' => 'REFRESH_TOKEN_ID',
        ]);
        $request = $request->withAttribute('client', $client);

        $grantTypeData = GrantTypeData::create($client);

        $this->getGrantTypeManager()
            ->get('refresh_token')
            ->grant($request, $grantTypeData)
        ;
        static::assertSame('CLIENT_ID', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertSame('CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }
}
