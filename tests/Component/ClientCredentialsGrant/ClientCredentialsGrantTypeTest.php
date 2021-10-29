<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientCredentialsGrant;

use OAuth2Framework\Component\ClientCredentialsGrant\ClientCredentialsGrantType;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
final class ClientCredentialsGrantTypeTest extends TestCase
{
    use ProphecyTrait;

    private ?ClientCredentialsGrantType $grantType = null;

    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame([], $this->getGrantType()->associatedResponseTypes());
        static::assertSame('client_credentials', $this->getGrantType()->name());
    }

    /**
     * @test
     */
    public function theRequestHaveAllRequiredParameters(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $this->getGrantType()
            ->checkRequest($request->reveal())
        ;
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPrepared(): void
    {
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

        $request = $this->prophesize(ServerRequestInterface::class);
        $grantTypeData = new GrantTypeData($client->reveal());

        $this->getGrantType()
            ->prepareResponse($request->reveal(), $grantTypeData)
        ;
        static::assertSame($grantTypeData, $grantTypeData);
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClient(): void
    {
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

        $request = $this->prophesize(ServerRequestInterface::class);
        $grantTypeData = new GrantTypeData($client->reveal());

        $this->getGrantType()
            ->grant($request->reveal(), $grantTypeData)
        ;
        static::assertSame('CLIENT_ID', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertSame('CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }

    private function getGrantType(): ClientCredentialsGrantType
    {
        if ($this->grantType === null) {
            $this->grantType = new ClientCredentialsGrantType();
        }

        return $this->grantType;
    }
}
