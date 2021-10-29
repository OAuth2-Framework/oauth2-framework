<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ImplicitGrant;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\ImplicitGrant\ImplicitGrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
final class ImplicitGrantTypeTest extends TestCase
{
    use ProphecyTrait;

    private ?ImplicitGrantType $grantType = null;

    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame(['token'], $this->getGrantType()->associatedResponseTypes());
        static::assertSame('implicit', $this->getGrantType()->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);

        try {
            $this->getGrantType()
                ->checkRequest($request->reveal())
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
        $request->getParsedBody()
            ->willReturn([
                'implicit' => 'REFRESH_TOKEN_ID',
            ])
        ;
        $grantTypeData = new GrantTypeData($client->reveal());

        try {
            $this->getGrantType()
                ->prepareResponse($request->reveal(), $grantTypeData)
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
        $request->getParsedBody()
            ->willReturn([
                'implicit' => 'REFRESH_TOKEN_ID',
            ])
        ;
        $request->getAttribute('client')
            ->willReturn($client)
        ;
        $grantTypeData = new GrantTypeData($client->reveal());

        try {
            $this->getGrantType()
                ->grant($request->reveal(), $grantTypeData)
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_grant',
                'error_description' => 'The implicit grant type cannot be called from the token endpoint.',
            ], $e->getData());
        }
    }

    private function getGrantType(): ImplicitGrantType
    {
        if ($this->grantType === null) {
            $this->grantType = new ImplicitGrantType();
        }

        return $this->grantType;
    }
}
