<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ResourceOwnerPasswordCredentialsGrant;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialManager;
use OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialsGrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
final class ResourceOwnerPasswordCredentialsGrantTypeTest extends TestCase
{
    use ProphecyTrait;

    private ?ResourceOwnerPasswordCredentialsGrantType $grantType = null;

    /**
     * @test
     */
    public function genericInformation(): void
    {
        static::assertSame([], $this->getGrantType()->associatedResponseTypes());
        static::assertSame('password', $this->getGrantType()->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters(): void
    {
        $request = $this->buildRequest([
            'password' => 'PASSWORD',
        ]);

        try {
            $this->getGrantType()
                ->checkRequest($request->reveal())
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
        $request = $this->buildRequest([
            'password' => 'PASSWORD',
            'username' => 'USERNAME',
        ]);

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

        $request = $this->buildRequest([
            'password' => 'PASSWORD',
            'username' => 'USERNAME',
        ]);
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

        $request = $this->buildRequest([
            'password' => 'PASSWORD',
            'username' => 'USERNAME',
        ]);
        $grantTypeData = new GrantTypeData($client->reveal());

        $this->getGrantType()
            ->grant($request->reveal(), $grantTypeData)
        ;
        static::assertSame('USERNAME', $grantTypeData->getResourceOwnerId()->getValue());
        static::assertSame('CLIENT_ID', $grantTypeData->getClient()->getPublicId()->getValue());
    }

    private function getGrantType(): ResourceOwnerPasswordCredentialsGrantType
    {
        if ($this->grantType === null) {
            $resourcOwnerPasswordCredentialManager = $this->prophesize(ResourceOwnerPasswordCredentialManager::class);
            $resourcOwnerPasswordCredentialManager->findResourceOwnerIdWithUsernameAndPassword('USERNAME', 'PASSWORD')
                ->willReturn(new UserAccountId('USERNAME'))
            ;

            $this->grantType = new ResourceOwnerPasswordCredentialsGrantType(
                $resourcOwnerPasswordCredentialManager->reveal()
            );
        }

        return $this->grantType;
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()
            ->willReturn(http_build_query($data))
        ;
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')
            ->willReturn(true)
        ;
        $request->getHeader('Content-Type')
            ->willReturn(['application/x-www-form-urlencoded'])
        ;
        $request->getBody()
            ->willReturn($body->reveal())
        ;
        $request->getParsedBody()
            ->willReturn([])
        ;

        return $request;
    }
}
