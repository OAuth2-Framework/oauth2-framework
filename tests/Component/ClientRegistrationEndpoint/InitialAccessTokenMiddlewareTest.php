<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRegistrationEndpoint;

use DateTimeImmutable;
use OAuth2Framework\Component\BearerTokenType\AuthorizationHeaderTokenFinder;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessToken as InitialAccessTokenInterface;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenMiddleware;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
final class InitialAccessTokenMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private ?InitialAccessTokenMiddleware $middleware = null;

    private ?object $repository = null;

    /**
     * @test
     */
    public function theInitialAccessTokenIsMissing(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn($response->reveal());
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('AUTHORIZATION')
            ->willReturn([])->shouldBeCalled();

        try {
            $this->getMiddleware()
                ->process($request->reveal(), $handler->reveal())
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Initial Access Token is missing or invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsNotKnown(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn($response->reveal());
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('AUTHORIZATION')
            ->willReturn(['Bearer BAD_INITIAL_ACCESS_TOKEN_ID'])->shouldBeCalled();

        try {
            $this->getMiddleware()
                ->process($request->reveal(), $handler->reveal())
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Initial Access Token is missing or invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsRevoked(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('AUTHORIZATION')
            ->willReturn(['Bearer REVOKED_INITIAL_ACCESS_TOKEN_ID'])->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getMiddleware()
                ->process($request->reveal(), $handler->reveal())
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Initial Access Token is missing or invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theInitialAccessTokenExpired(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('AUTHORIZATION')
            ->willReturn(['Bearer EXPIRED_INITIAL_ACCESS_TOKEN_ID'])->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getMiddleware()
                ->process($request->reveal(), $handler->reveal())
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Initial Access Token expired.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsValidAndAssociatedToTheRequest(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $response = $this->prophesize(ResponseInterface::class);
        $request->getHeader('AUTHORIZATION')
            ->willReturn(['Bearer INITIAL_ACCESS_TOKEN_ID'])->shouldBeCalled();
        $request->withAttribute('initial_access_token', Argument::type(InitialAccessToken::class))->willReturn(
            $request
        )->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn(
            $response->reveal()
        )->shouldBeCalled();

        $this->getMiddleware()
            ->process($request->reveal(), $handler->reveal())
        ;
    }

    private function getMiddleware(): InitialAccessTokenMiddleware
    {
        if ($this->middleware === null) {
            $bearerToken = new BearerToken('Realm');
            $bearerToken->addTokenFinder(new AuthorizationHeaderTokenFinder());
            $this->middleware = new InitialAccessTokenMiddleware($bearerToken, $this->getRepository(), false);
        }

        return $this->middleware;
    }

    private function getRepository(): InitialAccessTokenRepository
    {
        if ($this->repository === null) {
            $repository = $this->prophesize(InitialAccessTokenRepository::class);
            $repository->find(Argument::type(InitialAccessTokenId::class))->will(
                static function ($args): ?InitialAccessTokenInterface {
                switch ($args[0]->getValue()) {
                    case 'INITIAL_ACCESS_TOKEN_ID':
                        return new InitialAccessToken(
                            $args[0],
                            new UserAccountId('USER_ACCOUNT_ID'),
                            new DateTimeImmutable('now +1 day')
                        );
                    case 'REVOKED_INITIAL_ACCESS_TOKEN_ID':
                        $initialAccessToken = new InitialAccessToken(
                            $args[0],
                            new UserAccountId('USER_ACCOUNT_ID'),
                            new DateTimeImmutable('now +1 day')
                        );

                        return $initialAccessToken->markAsRevoked();
                    case 'EXPIRED_INITIAL_ACCESS_TOKEN_ID':
                        return new InitialAccessToken(
                            $args[0],
                            new UserAccountId('USER_ACCOUNT_ID'),
                            new DateTimeImmutable('now -1 day')
                        );
                    case 'BAD_INITIAL_ACCESS_TOKEN_ID':
                    default:
                        return null;
                }
            }
            );

            $this->repository = $repository->reveal();
        }

        return $this->repository;
    }
}
