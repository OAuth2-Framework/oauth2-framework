<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationCodeGrant;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class AuthorizationCodeGrantTypeTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame(
            ['code'],
            $this->getGrantTypeManager()
                ->get('authorization_code')
                ->associatedResponseTypes()
        );
        static::assertSame('authorization_code', $this->getGrantTypeManager()->get('authorization_code')->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters(): void
    {
        $request = $this->buildRequest('GET', []);

        try {
            $this->getGrantTypeManager()
                ->get('authorization_code')
                ->checkRequest($request)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Missing grant type parameter(s): code, redirect_uri.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRequestHaveAllRequiredParameters(): void
    {
        $request = $this->buildRequest('GET', [
            'code' => 'AUTHORIZATION_CODE_ID',
            'redirect_uri' => 'http://localhost:8000/',
        ]);

        $this->getGrantTypeManager()
            ->get('authorization_code')
            ->checkRequest($request)
        ;
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPrepared(): void
    {
        $client = Client::create(ClientId::create('CLIENT_ID'), DataBag::create(), null);
        $request = $this->buildRequest('GET', [
            'code' => 'AUTHORIZATION_CODE_ID',
            'redirect_uri' => 'http://localhost:8000/',
        ]);
        $grantTypeData = GrantTypeData::create($client);

        $this->getGrantTypeManager()
            ->get('authorization_code')
            ->prepareResponse($request, $grantTypeData)
        ;
        static::assertSame($grantTypeData, $grantTypeData);
    }

    /**
     * @test
     */
    public function theGrantTypeCannotGrantTheClientAsTheCodeVerifierIsMissing(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_endpoint_auth_method' => 'none',
            ]),
            null
        );

        $request = $this->buildRequest('GET', [
            'client_id' => 'CLIENT_ID',
            'code' => 'AUTHORIZATION_CODE_ID',
            'redirect_uri' => 'http://localhost:8000/',
        ]);
        $request = $request->withAttribute('client', $client);
        $grantTypeData = GrantTypeData::create($client);

        try {
            $this->getGrantTypeManager()
                ->get('authorization_code')
                ->grant($request, $grantTypeData)
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_grant',
                'error_description' => 'The parameter "code_verifier" is missing or invalid.',
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
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_jwt',
            ]),
            null
        );

        $request = $this->buildRequest('GET', [
            'code' => 'AUTHORIZATION_CODE_ID',
            'redirect_uri' => 'http://localhost:8000/',
            'code_verifier' => 'ABCDEFGH',
        ]);
        $request = $request->withAttribute('client', $client);
        $grantTypeData = GrantTypeData::create($client);

        $this->getGrantTypeManager()
            ->get('authorization_code')
            ->grant($request, $grantTypeData)
        ;
        static::assertSame('USER_ACCOUNT_ID', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertSame('CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }
}
