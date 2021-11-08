<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientCredentialsGrant;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class ClientCredentialsGrantTypeTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame([], $this->getGrantTypeManager()->get('client_credentials')->associatedResponseTypes());
        static::assertSame('client_credentials', $this->getGrantTypeManager()->get('client_credentials')->name());
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

        $request = $this->buildRequest();
        $grantTypeData = GrantTypeData::create($client);

        $this->getGrantTypeManager()
            ->get('client_credentials')
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

        $request = $this->buildRequest();
        $grantTypeData = GrantTypeData::create($client);

        $this->getGrantTypeManager()
            ->get('client_credentials')
            ->grant($request, $grantTypeData)
        ;
        static::assertSame('CLIENT_ID', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertSame('CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }
}
