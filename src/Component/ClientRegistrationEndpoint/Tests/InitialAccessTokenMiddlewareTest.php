<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\ClientRegistrationEndpoint\Tests;

use Psr\Http\Server\RequestHandlerInterface;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenMiddleware;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group InitialAccessTokenMiddleware
 */
class InitialAccessTokenMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function theInitialAccessTokenIsMissing()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('AUTHORIZATION')->willReturn([])->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getMiddleware()->process($request->reveal(), $handler->reveal());
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
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
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('AUTHORIZATION')->willReturn([
            'Bearer BAD_INITIAL_ACCESS_TOKEN_ID',
        ])->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getMiddleware()->process($request->reveal(), $handler->reveal());
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
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
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
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
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
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

    /**
     * @var null|InitialAccessTokenMiddleware
     */
    private $middleware = null;

    /**
     * @return InitialAccessTokenMiddleware
     */
    private function getMiddleware(): InitialAccessTokenMiddleware
    {
        if (null === $this->middleware) {
            $this->middleware = new InitialAccessTokenMiddleware(
                new BearerToken('Realm', true, false, false),
                $this->getRepository()
            );
        }

        return $this->middleware;
    }

    /**
     * @var null|InitialAccessTokenRepository
     */
    private $repository = null;

    /**
     * @return InitialAccessTokenRepository
     */
    private function getRepository(): InitialAccessTokenRepository
    {
        if (null === $this->repository) {
            $repository = $this->prophesize(InitialAccessTokenRepository::class);
            $repository->find(Argument::type(InitialAccessTokenId::class))->will(function ($args) {
                switch ($args[0]->getValue()) {
                    case 'INITIAL_ACCESS_TOKEN_ID':
                        $initialAccessToken = InitialAccessToken::createEmpty();
                        $initialAccessToken = $initialAccessToken->create(
                            $args[0],
                            UserAccountId::create('USER_ACCOUNT_ID'),
                            new \DateTimeImmutable('now +1 day')
                        );
                        $initialAccessToken->eraseMessages();

                        return $initialAccessToken;
                    case 'REVOKED_INITIAL_ACCESS_TOKEN_ID':
                        $initialAccessToken = InitialAccessToken::createEmpty();
                        $initialAccessToken = $initialAccessToken->create(
                            $args[0],
                            UserAccountId::create('USER_ACCOUNT_ID'),
                            new \DateTimeImmutable('now +1 day')
                        );
                        $initialAccessToken = $initialAccessToken->markAsRevoked();
                        $initialAccessToken->eraseMessages();

                        return $initialAccessToken;
                    case 'EXPIRED_INITIAL_ACCESS_TOKEN_ID':
                        $initialAccessToken = InitialAccessToken::createEmpty();
                        $initialAccessToken = $initialAccessToken->create(
                            $args[0],
                            UserAccountId::create('USER_ACCOUNT_ID'),
                            new \DateTimeImmutable('now -1 day')
                        );
                        $initialAccessToken->eraseMessages();

                        return $initialAccessToken;
                    case 'BAD_INITIAL_ACCESS_TOKEN_ID':
                    default:
                        return null;
                }
            });

            $this->repository = $repository->reveal();
        }

        return $this->repository;
    }
}
