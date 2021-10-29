<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\NoneGrant;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\NoneGrant\AuthorizationStorage;
use OAuth2Framework\Component\NoneGrant\NoneResponseType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class TokenResponseTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function genericInformation(): void
    {
        $authorizationStorage = $this->prophesize(AuthorizationStorage::class);
        $responseType = new NoneResponseType($authorizationStorage->reveal());

        static::assertSame([], $responseType->associatedGrantTypes());
        static::assertSame('none', $responseType->name());
        static::assertSame('query', $responseType->getResponseMode());
    }

    /**
     * @test
     */
    public function theAuthorizationIsSaved(): void
    {
        $authorizationStorage = $this->prophesize(AuthorizationStorage::class);
        $authorizationStorage->save(Argument::type(AuthorizationRequest::class))->shouldBeCalled();
        $responseType = new NoneResponseType($authorizationStorage->reveal());

        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;
        $client->getClientId()
            ->willReturn(new ClientId('CLIENT_ID'))
        ;
        $client->getOwnerId()
            ->willReturn(new UserAccountId('USER_ACCOUNT_ID'))
        ;

        $tokenType = $this->prophesize(TokenType::class);
        $tokenType->getAdditionalInformation()
            ->willReturn([
                'token_type' => 'FOO',
            ])
        ;

        $authorization = new AuthorizationRequest($client->reveal(), []);

        $responseType->process($authorization, $tokenType->reveal());

        static::assertSame('CLIENT_ID', $authorization->getClient()->getPublicId()->getValue());
        static::assertFalse($authorization->hasResponseParameter('access_token'));
    }
}
