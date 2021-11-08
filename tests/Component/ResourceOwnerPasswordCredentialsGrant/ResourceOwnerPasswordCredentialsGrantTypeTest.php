<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ResourceOwnerPasswordCredentialsGrant;

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
final class ResourceOwnerPasswordCredentialsGrantTypeTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame([], $this->getGrantTypeManager()->get('password')->associatedResponseTypes());
        static::assertSame('password', $this->getGrantTypeManager()->get('password')->name());
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
                ->get('password')
                ->checkRequest($request)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Missing grant type parameter(s): username.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRequestHaveAllRequiredParameters(): void
    {
        $request = $this->buildRequest('GET', [
            'password' => 'PASSWORD',
            'username' => 'USERNAME',
        ]);

        $this->getGrantTypeManager()
            ->get('password')
            ->checkRequest($request)
        ;
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPrepared(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'password' => 'PASSWORD',
            'username' => 'USERNAME',
        ]);
        $grantTypeData = GrantTypeData::create($client);

        $this->getGrantTypeManager()
            ->get('password')
            ->prepareResponse($request, $grantTypeData)
        ;
        static::assertSame($grantTypeData, $grantTypeData);
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
            'password' => 'password.1',
            'username' => 'john.1',
        ]);
        $grantTypeData = GrantTypeData::create($client);

        $this->getGrantTypeManager()
            ->get('password')
            ->grant($request, $grantTypeData)
        ;
        static::assertSame('john.1', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertSame('CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }
}
