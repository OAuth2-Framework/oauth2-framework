<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientAuthentication;

use OAuth2Framework\Component\ClientAuthentication\ClientSecretPost;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
final class ClientSecretPostAuthenticationMethodTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function genericCalls(): void
    {
        $method = new ClientSecretPost();

        static::assertSame([], $method->getSchemesParameters());
        static::assertSame(['client_secret_post'], $method->getSupportedMethods());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest(): void
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest([]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdHasBeenFoundInTheRequestButNoClientSecret(): void
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest([
            'client_id' => 'CLIENT_ID',
        ]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdAndClientSecretHaveBeenFoundInTheRequest(): void
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest([
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        static::assertInstanceOf(ClientId::class, $clientId);
        static::assertSame('CLIENT_SECRET', $credentials);
    }

    /**
     * @test
     */
    public function theClientIsAuthenticated(): void
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest([
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

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
        $client->has('token_endpoint_auth_method')
            ->willReturn(true)
        ;
        $client->get('token_endpoint_auth_method')
            ->willReturn('client_secret_basic')
        ;
        $client->getTokenEndpointAuthenticationMethod()
            ->willReturn('client_secret_basic')
        ;
        $client->has('client_secret')
            ->willReturn(true)
        ;
        $client->get('client_secret')
            ->willReturn('CLIENT_SECRET')
        ;
        $client->isDeleted()
            ->willReturn(false)
        ;
        $client->areClientCredentialsExpired()
            ->willReturn(false)
        ;

        static::assertTrue($method->isClientAuthenticated($client->reveal(), 'CLIENT_SECRET', $request->reveal()));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeChecked(): void
    {
        $method = new ClientSecretPost();
        $validatedParameters = $method->checkClientConfiguration(new DataBag([]), new DataBag([]));

        static::assertTrue($validatedParameters->has('client_secret'));
        static::assertTrue($validatedParameters->has('client_secret_expires_at'));
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
