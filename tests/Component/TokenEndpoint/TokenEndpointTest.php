<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\TokenEndpoint;

use DateTimeImmutable;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtensionManager;
use OAuth2Framework\Component\TokenEndpoint\TokenEndpoint;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
final class TokenEndpointTest extends TestCase
{
    use ProphecyTrait;

    private ?TokenEndpoint $tokenEndpoint = null;

    private ?object $clientRepository = null;

    private ?object $userAccountRepository = null;

    private ?object $accessTokenRepository = null;

    /**
     * @test
     */
    public function unauthenticatedClient(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('grant_type')
            ->willReturn(new FooGrantType())
            ->shouldBeCalled()
        ;
        $request->getAttribute('client')
            ->willReturn(null)
            ->shouldBeCalled()
        ;

        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getTokenEndpoint()
                ->process($request->reveal(), $handler->reveal())
            ;
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
    public function theClientIsNotAllowedToUseTheGrantType(): void
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
            ->willReturn(new UserAccountId('OWNER_ID'))
        ;
        $client->isDeleted()
            ->willReturn(false)
        ;
        $client->isGrantTypeAllowed('foo')
            ->willReturn(false)
        ;

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('grant_type')
            ->willReturn(new FooGrantType())
            ->shouldBeCalled()
        ;
        $request->getAttribute('client')
            ->willReturn($client->reveal())
            ->shouldBeCalled()
        ;

        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getTokenEndpoint()
                ->process($request->reveal(), $handler->reveal())
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'unauthorized_client',
                'error_description' => 'The grant type "foo" is unauthorized for this client.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theTokenRequestIsValidAndAnAccessTokenIsIssued(): void
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
            ->willReturn(new UserAccountId('OWNER_ID'))
        ;
        $client->isDeleted()
            ->willReturn(false)
        ;
        $client->isGrantTypeAllowed('foo')
            ->willReturn(true)
        ;

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('grant_type')
            ->willReturn(new FooGrantType())
            ->shouldBeCalled()
        ;
        $request->getAttribute('client')
            ->willReturn($client->reveal())
            ->shouldBeCalled()
        ;

        $tokenType = $this->prophesize(TokenType::class);
        $tokenType->name()
            ->willReturn('TOKEN_TYPE')
            ->shouldBeCalled()
        ;
        $tokenType->getAdditionalInformation()
            ->willReturn([
                'token_type_foo' => 'token_type_bar',
            ])->shouldBeCalled();
        $request->getAttribute('token_type')
            ->willReturn($tokenType->reveal())
            ->shouldBeCalled()
        ;

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled()
        ;

        $response = $this->getTokenEndpoint()
            ->process($request->reveal(), $handler->reveal())
        ;
        $response->getBody()
            ->rewind()
        ;
        $body = $response->getBody()
            ->getContents()
        ;

        static::assertSame(200, $response->getStatusCode());
        static::assertMatchesRegularExpression(
            '/^\{"token_type_foo"\:"token_type_bar","token_type"\:"TOKEN_TYPE","access_token"\:"[a-f0-9]{64}","expires_in"\:\d{4}\}$/',
            $body
        );
    }

    private function getTokenEndpoint(): TokenEndpoint
    {
        if ($this->tokenEndpoint === null) {
            $this->tokenEndpoint = new TokenEndpoint(
                $this->getClientRepository(),
                $this->getUserAccountRepository(),
                new TokenEndpointExtensionManager(),
                new Psr17Factory(),
                $this->getAccessTokenRepository(),
                1800
            );
        }

        return $this->tokenEndpoint;
    }

    private function getClientRepository(): ClientRepository
    {
        if ($this->clientRepository === null) {
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
                ->willReturn(new UserAccountId('OWNER_ID'))
            ;
            $client->isDeleted()
                ->willReturn(false)
            ;
            $client->has('grant_types')
                ->willReturn(true)
            ;
            $client->get('grant_types')
                ->willReturn(['foo'])
            ;

            $clientRepository = $this->prophesize(ClientRepository::class);
            $clientRepository->find(Argument::type(ClientId::class))->willReturn($client->reveal());

            $this->clientRepository = $clientRepository->reveal();
        }

        return $this->clientRepository;
    }

    private function getUserAccountRepository(): UserAccountRepository
    {
        if ($this->userAccountRepository === null) {
            $userAccountRepository = $this->prophesize(UserAccountRepository::class);

            $this->userAccountRepository = $userAccountRepository->reveal();
        }

        return $this->userAccountRepository;
    }

    private function getAccessTokenRepository(): AccessTokenRepository
    {
        if ($this->accessTokenRepository === null) {
            $accessTokenRepository = $this->prophesize(AccessTokenRepository::class);
            $accessTokenRepository->create(
                Argument::type(ClientId::class),
                Argument::type(ResourceOwnerId::class),
                Argument::type(DateTimeImmutable::class),
                Argument::type(DataBag::class),
                Argument::type(DataBag::class),
                Argument::any()
            )
                ->will(function (array $args) {
                    return new AccessToken(new AccessTokenId(bin2hex(
                        random_bytes(32)
                    )), $args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
                })
            ;
            $accessTokenRepository->save(Argument::type(AccessToken::class))->will(function (array $args) {
            });
            $this->accessTokenRepository = $accessTokenRepository->reveal();
        }

        return $this->accessTokenRepository;
    }
}
