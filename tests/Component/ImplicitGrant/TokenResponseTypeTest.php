<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ImplicitGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;
use OAuth2Framework\Tests\TestBundle\Entity\UserAccount;

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
        static::assertSame(['implicit'], $this->getTokenResponseType()->associatedGrantTypes());
        static::assertSame('token', $this->getTokenResponseType()->name());
        static::assertSame('fragment', $this->getTokenResponseType()->getResponseMode());
    }

    /**
     * @test
     */
    public function anAccessTokenIsCreatedDuringTheAuthorizationProcess(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create(),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $userAccount = UserAccount::create(
            UserAccountId::create('john.1'),
            'john.1',
            [],
            new DateTimeImmutable('now -100 seconds'),
            null,
            []
        );

        $authorization = AuthorizationRequest::create($client, [])
            ->setUserAccount($userAccount)
        ;

        $this->getTokenResponseType()
            ->process($authorization, BearerToken::create('REALM'))
        ;

        static::assertSame('CLIENT_ID', $authorization->getClient()->getPublicId()->getValue());
        static::assertTrue($authorization->hasResponseParameter('access_token'));
    }
}
