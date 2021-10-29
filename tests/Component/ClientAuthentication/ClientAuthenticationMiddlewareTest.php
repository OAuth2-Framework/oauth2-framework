<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientAuthentication;

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethod;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientAuthenticationMiddleware;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretBasic;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
final class ClientAuthenticationMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function noClientIsFoundInTheRequest(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn([])->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $clientRepository = $this->prophesize(ClientRepository::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldBeCalled()
            ->willReturn($response->reveal())
        ;

        $this->getClientAuthenticationMiddleware($clientRepository->reveal())
            ->process($request->reveal(), $handler->reveal())
        ;
    }

    /**
     * @test
     */
    public function aClientIdIsSetButTheClientDoesNotExist(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn(['Basic ' . base64_encode('FOO:BAR')])
            ->shouldBeCalled()
        ;
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::type(ClientId::class))->willReturn(null)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled()
        ;

        try {
            $this->getClientAuthenticationMiddleware($clientRepository->reveal())
                ->process($request->reveal(), $handler->reveal())
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(401, $e->getCode());
            static::assertSame([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function aClientIdIsSetButTheClientIsDeleted(): void
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('FOO'))
        ;
        $client->getClientId()
            ->willReturn(new ClientId('FOO'))
        ;
        $client->isDeleted()
            ->willReturn(true)
        ;

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn(['Basic ' . base64_encode('FOO:BAR')])
            ->shouldBeCalled()
        ;
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::type(ClientId::class))->willReturn($client)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled()
        ;

        try {
            $this->getClientAuthenticationMiddleware($clientRepository->reveal())
                ->process($request->reveal(), $handler->reveal())
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(401, $e->getCode());
            static::assertSame([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function aClientIdIsSetButTheClientCredentialsExpired(): void
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('FOO'))
        ;
        $client->getClientId()
            ->willReturn(new ClientId('FOO'))
        ;
        $client->get('token_endpoint_auth_method')
            ->willReturn('client_secret_basic')
        ;
        $client->getTokenEndpointAuthenticationMethod()
            ->willReturn('client_secret_basic')
        ;
        $client->isDeleted()
            ->willReturn(false)
        ;
        $client->has('client_secret')
            ->willReturn(false)
        ;
        $client->get('client_secret')
            ->willReturn('BAR')
        ;
        $client->areClientCredentialsExpired()
            ->willReturn(true)
        ;

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn(['Basic ' . base64_encode('FOO:BAR')])
            ->shouldBeCalled()
        ;
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::type(ClientId::class))->willReturn($client)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled()
        ;

        try {
            $this->getClientAuthenticationMiddleware($clientRepository->reveal())
                ->process($request->reveal(), $handler->reveal())
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(401, $e->getCode());
            static::assertSame([
                'error' => 'invalid_client',
                'error_description' => 'Client credentials expired.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function aClientIdIsSetButTheAuthenticationMethodIsNotSupportedByTheClient(): void
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('FOO'))
        ;
        $client->getClientId()
            ->willReturn(new ClientId('FOO'))
        ;
        $client->has('token_endpoint_auth_method')
            ->willReturn(true)
        ;
        $client->get('token_endpoint_auth_method')
            ->willReturn('none')
        ;
        $client->getTokenEndpointAuthenticationMethod()
            ->willReturn('none')
        ;
        $client->isDeleted()
            ->willReturn(false)
        ;
        $client->areClientCredentialsExpired()
            ->willReturn(false)
        ;

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn(['Basic ' . base64_encode('FOO:BAR')])
            ->shouldBeCalled()
        ;
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::type(ClientId::class))->willReturn($client)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled()
        ;

        try {
            $this->getClientAuthenticationMiddleware($clientRepository->reveal())
                ->process($request->reveal(), $handler->reveal())
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(401, $e->getCode());
            static::assertSame([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function aClientIdIsSetButTheClientIsNotAuthenticated(): void
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('FOO'))
        ;
        $client->getClientId()
            ->willReturn(new ClientId('FOO'))
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
            ->willReturn('BAR')
        ;
        $client->isDeleted()
            ->willReturn(false)
        ;
        $client->areClientCredentialsExpired()
            ->willReturn(false)
        ;

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn(['Basic ' . base64_encode('FOO:BAD_SECRET')])
            ->shouldBeCalled()
        ;
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::type(ClientId::class))->willReturn($client)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled()
        ;

        try {
            $this->getClientAuthenticationMiddleware($clientRepository->reveal())
                ->process($request->reveal(), $handler->reveal())
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(401, $e->getCode());
            static::assertSame([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function aClientIsFullyAuthenticated(): void
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()
            ->willReturn(false)
        ;
        $client->getPublicId()
            ->willReturn(new ClientId('FOO'))
        ;
        $client->getClientId()
            ->willReturn(new ClientId('FOO'))
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
            ->willReturn('BAR')
        ;
        $client->isDeleted()
            ->willReturn(false)
        ;
        $client->areClientCredentialsExpired()
            ->willReturn(false)
        ;

        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn(['Basic ' . base64_encode('FOO:BAR')])
            ->shouldBeCalled()
        ;
        $request->withAttribute('client', $client)
            ->shouldBeCalled()
            ->willReturn($request->reveal())
        ;
        $request->withAttribute(
            'client_authentication_method',
            Argument::type(AuthenticationMethod::class)
        )->shouldBeCalled()
            ->willReturn($request->reveal())
        ;
        $request->withAttribute('client_credentials', 'BAR')
            ->shouldBeCalled()
            ->willReturn($request->reveal())
        ;
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::type(ClientId::class))->willReturn($client)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldBeCalled()
            ->willReturn($response->reveal())
        ;

        $this->getClientAuthenticationMiddleware($clientRepository->reveal())
            ->process($request->reveal(), $handler->reveal())
        ;
    }

    private function getClientAuthenticationMiddleware(
        ClientRepository $clientRepository
    ): ClientAuthenticationMiddleware {
        $authenticationMethodManager = new AuthenticationMethodManager();
        $authenticationMethodManager->add(new ClientSecretBasic('Real'));

        return new ClientAuthenticationMiddleware($clientRepository, $authenticationMethodManager);
    }
}
