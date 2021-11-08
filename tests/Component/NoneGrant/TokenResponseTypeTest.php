<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\NoneGrant;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class TokenResponseTypeTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericInformation(): void
    {
        $responseType = $this->getResponseTypeManager()
            ->get('none')
        ;

        static::assertSame([], $responseType->associatedGrantTypes());
        static::assertSame('none', $responseType->name());
        static::assertSame('query', $responseType->getResponseMode());
    }

    /**
     * @test
     */
    public function theAuthorizationIsSaved(): void
    {
        $responseType = $this->getResponseTypeManager()
            ->get('none')
        ;

        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $authorization = AuthorizationRequest::create($client, []);

        $responseType->process($authorization, BearerToken::create('REALM'));

        static::assertSame('CLIENT_ID', $authorization->getClient()->getPublicId()->getValue());
        static::assertFalse($authorization->hasResponseParameter('access_token'));
    }
}
