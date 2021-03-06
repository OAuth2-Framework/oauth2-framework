<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Tests\Component\ClientRegistrationEndpoint;

use OAuth2Framework\Component\BearerTokenType\AuthorizationHeaderTokenFinder;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
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
 * @group InitialAccessTokenMiddleware
 *
 * @internal
 */
final class InitialAccessTokenMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var null|InitialAccessTokenMiddleware
     */
    private $middleware;

    /**
     * @var null|InitialAccessTokenRepository
     */
    private $repository;

    /**
     * @test
     */
    public function theInitialAccessTokenIsMissing()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn($response->reveal());
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('AUTHORIZATION')->willReturn([])->shouldBeCalled();

        try {
            $this->getMiddleware()->process($request->reveal(), $handler->reveal());
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'Initial Access Token is missing or invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsNotKnown()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn($response->reveal());
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('AUTHORIZATION')->willReturn([
            'Bearer BAD_INITIAL_ACCESS_TOKEN_ID',
        ])->shouldBeCalled();

        try {
            $this->getMiddleware()->process($request->reveal(), $handler->reveal());
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'Initial Access Token is missing or invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsRevoked()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('AUTHORIZATION')->willReturn([
            'Bearer REVOKED_INITIAL_ACCESS_TOKEN_ID',
        ])->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getMiddleware()->process($request->reveal(), $handler->reveal());
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'Initial Access Token is missing or invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theInitialAccessTokenExpired()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('AUTHORIZATION')->willReturn([
            'Bearer EXPIRED_INITIAL_ACCESS_TOKEN_ID',
        ])->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getMiddleware()->process($request->reveal(), $handler->reveal());
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'Initial Access Token expired.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsValidAndAssociatedToTheRequest()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $response = $this->prophesize(ResponseInterface::class);
        $request->getHeader('AUTHORIZATION')->willReturn([
            'Bearer INITIAL_ACCESS_TOKEN_ID',
        ])->shouldBeCalled();
        $request->withAttribute('initial_access_token', Argument::type(InitialAccessToken::class))->willReturn($request)->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn($response->reveal())->shouldBeCalled();

        $this->getMiddleware()->process($request->reveal(), $handler->reveal());
    }

    private function getMiddleware(): InitialAccessTokenMiddleware
    {
        if (null === $this->middleware) {
            $bearerToken = new BearerToken('Realm');
            $bearerToken->addTokenFinder(new AuthorizationHeaderTokenFinder());
            $this->middleware = new InitialAccessTokenMiddleware(
                $bearerToken,
                $this->getRepository(),
                false
            );
        }

        return $this->middleware;
    }

    private function getRepository(): InitialAccessTokenRepository
    {
        if (null === $this->repository) {
            $repository = $this->prophesize(InitialAccessTokenRepository::class);
            $repository->find(Argument::type(InitialAccessTokenId::class))->will(function ($args) {
                switch ($args[0]->getValue()) {
                    case 'INITIAL_ACCESS_TOKEN_ID':
                        $initialAccessToken = new InitialAccessToken(
                            $args[0],
                            new UserAccountId('USER_ACCOUNT_ID'),
                            new \DateTimeImmutable('now +1 day')
                        );

                        return $initialAccessToken;
                    case 'REVOKED_INITIAL_ACCESS_TOKEN_ID':
                        $initialAccessToken = new InitialAccessToken(
                            $args[0],
                            new UserAccountId('USER_ACCOUNT_ID'),
                            new \DateTimeImmutable('now +1 day')
                        );

                        return $initialAccessToken->markAsRevoked();
                    case 'EXPIRED_INITIAL_ACCESS_TOKEN_ID':
                        $initialAccessToken = new InitialAccessToken(
                            $args[0],
                            new UserAccountId('USER_ACCOUNT_ID'),
                            new \DateTimeImmutable('now -1 day')
                        );

                        return $initialAccessToken;
                    case 'BAD_INITIAL_ACCESS_TOKEN_ID':
                    default:
                        return;
                }
            });

            $this->repository = $repository->reveal();
        }

        return $this->repository;
    }
}
