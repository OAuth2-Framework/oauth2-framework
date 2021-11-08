<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ImplicitGrant;

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
final class ImplicitGrantTypeTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame(['token'], $this->getGrantTypeManager()->get('implicit')->associatedResponseTypes());
        static::assertSame('implicit', $this->getGrantTypeManager()->get('implicit')->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters(): void
    {
        $request = $this->buildRequest();

        try {
            $this->getGrantTypeManager()
                ->get('implicit')
                ->checkRequest($request)
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_grant',
                'error_description' => 'The implicit grant type cannot be called from the token endpoint.',
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

        $request = $this->buildRequest('POST', [
            'implicit' => 'REFRESH_TOKEN_ID',
        ]);
        $grantTypeData = GrantTypeData::create($client);

        try {
            $this->getGrantTypeManager()
                ->get('implicit')
                ->prepareResponse($request, $grantTypeData)
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_grant',
                'error_description' => 'The implicit grant type cannot be called from the token endpoint.',
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
            'implicit' => 'REFRESH_TOKEN_ID',
        ])->withAttribute('client', $client);
        $grantTypeData = GrantTypeData::create($client);

        try {
            $this->getGrantTypeManager()
                ->get('implicit')
                ->grant($request, $grantTypeData)
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_grant',
                'error_description' => 'The implicit grant type cannot be called from the token endpoint.',
            ], $e->getData());
        }
    }
}
