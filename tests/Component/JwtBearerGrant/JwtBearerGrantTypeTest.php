<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\JwtBearerGrant;

use Jose\Component\Encryption\JWEBuilder;
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
final class JwtBearerGrantTypeTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame(
            [],
            $this->getGrantTypeManager()
                ->get('urn:ietf:params:oauth:grant-type:jwt-bearer')
                ->associatedResponseTypes()
        );
        static::assertSame(
            'urn:ietf:params:oauth:grant-type:jwt-bearer',
            $this->getGrantTypeManager()
                ->get('urn:ietf:params:oauth:grant-type:jwt-bearer')
                ->name()
        );
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters(): void
    {
        $request = $this->buildRequest('GET', []);

        try {
            $this->getGrantTypeManager()
                ->get('urn:ietf:params:oauth:grant-type:jwt-bearer')
                ->checkRequest($request)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Missing grant type parameter(s): assertion.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRequestHaveAllRequiredParameters(): void
    {
        $request = $this->buildRequest('GET', [
            'assertion' => 'FOO',
        ]);

        $this->getGrantTypeManager()
            ->get('urn:ietf:params:oauth:grant-type:jwt-bearer')
            ->checkRequest($request)
        ;
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPreparedWithAssertionFromClient(): void
    {
        if (! class_exists(JWEBuilder::class)) {
            static::markTestSkipped('The component "web-token/jwt-encryption" is not installed.');
        }
        $request = $this->buildRequest('GET', [
            'assertion' => $this->createValidEncryptedAssertionFromClient(),
        ]);
        $grantTypeData = GrantTypeData::create(null);

        $this->getGrantTypeManager()
            ->get('urn:ietf:params:oauth:grant-type:jwt-bearer')
            ->prepareResponse($request, $grantTypeData)
        ;
        static::assertTrue($grantTypeData->getMetadata()->has('jwt'));
        static::assertTrue($grantTypeData->getMetadata()->has('claims'));
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPreparedWithAssertionFromTrustedIssuer(): void
    {
        $request = $this->buildRequest('GET', [
            'assertion' => $this->createValidAssertionFromIssuer(),
        ]);
        $grantTypeData = GrantTypeData::create(null);

        $this->getGrantTypeManager()
            ->get('urn:ietf:params:oauth:grant-type:jwt-bearer')
            ->prepareResponse($request, $grantTypeData)
        ;
        static::assertTrue($grantTypeData->getMetadata()->has('jwt'));
        static::assertTrue($grantTypeData->getMetadata()->has('claims'));
    }

    /**
     * @test
     */
    public function theAssertionHasBeenIssuedByAnUnknownIssuer(): void
    {
        $request = $this->buildRequest('GET', [
            'assertion' => $this->createAssertionFromUnknownIssuer(),
        ]);
        $grantTypeData = GrantTypeData::create(null);

        try {
            $this->getGrantTypeManager()
                ->get('urn:ietf:params:oauth:grant-type:jwt-bearer')
                ->prepareResponse($request, $grantTypeData)
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Unable to find the issuer of the assertion.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClientUsingTheTokenIssuedByATrustedIssuer(): void
    {
        $client = Client::create(
            ClientId::create('PRIVATE_KEY_JWT_CLIENT_ID'),
            DataBag::create(),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'assertion' => $this->createValidAssertionFromIssuer(),
        ]);
        $request = $request->withAttribute('client', $client);
        $grantTypeData = GrantTypeData::create($client);
        $grantTypeData->setResourceOwnerId(UserAccountId::create('USER_ACCOUNT_ID'));

        $this->getGrantTypeManager()
            ->get('urn:ietf:params:oauth:grant-type:jwt-bearer')
            ->grant($request, $grantTypeData)
        ;
        static::assertSame($grantTypeData, $grantTypeData);
        static::assertSame('USER_ACCOUNT_ID', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertSame('PRIVATE_KEY_JWT_CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClientUsingTheTokenIssuedByTheClient(): void
    {
        if (! class_exists(JWEBuilder::class)) {
            static::markTestSkipped('The component "web-token/jwt-encryption" is not installed.');
        }
        $client = Client::create(
            ClientId::create('PRIVATE_KEY_JWT_CLIENT_ID'),
            DataBag::create(),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'assertion' => $this->createValidEncryptedAssertionFromClient(),
        ]);
        $request = $request->withAttribute('client', $client);
        $grantTypeData = GrantTypeData::create($client);
        $grantTypeData->setResourceOwnerId(UserAccountId::create('PRIVATE_KEY_JWT_CLIENT_ID'));

        $this->getGrantTypeManager()
            ->get('urn:ietf:params:oauth:grant-type:jwt-bearer')
            ->grant($request, $grantTypeData)
        ;
        static::assertSame($grantTypeData, $grantTypeData);
        static::assertSame('PRIVATE_KEY_JWT_CLIENT_ID', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertSame('PRIVATE_KEY_JWT_CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }
}
