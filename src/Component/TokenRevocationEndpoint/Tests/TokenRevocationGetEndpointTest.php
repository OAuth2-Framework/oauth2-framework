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

namespace OAuth2Framework\Component\TokenRevocationEndpoint\Tests;

use Http\Message\MessageFactory\DiactorosMessageFactory;
use Http\Message\ResponseFactory;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationGetEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHintManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group TokenRevocationEndpoint
 */
final class TokenRevocationGetEndpointTest extends TestCase
{
    /**
     * @test
     */
    public function aTokenTypeHintManagerCanHandleTokenTypeHints()
    {
        static::assertNotEmpty($this->getTokenTypeHintManager()->getTokenTypeHints());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesAValidGetRequest()
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn(['token' => 'VALID_TOKEN']);
        $request->getAttribute('client')->willReturn($this->getClient());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        static::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        static::assertEquals('', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesAValidGetRequestWithTokenTypeHint()
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn(['token' => 'VALID_TOKEN', 'token_type_hint' => 'foo']);
        $request->getAttribute('client')->willReturn($this->getClient());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        static::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        static::assertEquals('', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesAValidGetRequestWithCallback()
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn(['token' => 'VALID_TOKEN', 'callback' => 'callThisFunctionPlease']);
        $request->getAttribute('client')->willReturn($this->getClient());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        static::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        static::assertEquals('callThisFunctionPlease()', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenDoesNotExistAndCannotBeRevoked()
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn(['token' => 'UNKNOWN_TOKEN', 'callback' => 'callThisFunctionPlease']);
        $request->getAttribute('client')->willReturn($this->getClient());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        static::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        static::assertEquals('callThisFunctionPlease()', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesFromAnotherClient()
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn(['token' => 'TOKEN_FOR_ANOTHER_CLIENT', 'callback' => 'callThisFunctionPlease']);
        $request->getAttribute('client')->willReturn($this->getClient());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        static::assertEquals(400, $response->getStatusCode());
        $response->getBody()->rewind();
        static::assertEquals('callThisFunctionPlease({"error":"invalid_request","error_description":"The parameter \"token\" is invalid."})', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesARequestWithAnUnsupportedTokenHint()
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn(['token' => 'VALID_TOKEN', 'token_type_hint' => 'bar']);
        $request->getAttribute('client')->willReturn($this->getClient());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        static::assertEquals(400, $response->getStatusCode());
        $response->getBody()->rewind();
        static::assertEquals('{"error":"unsupported_token_type","error_description":"The token type hint \"bar\" is not supported. Please use one of the following values: foo."}', $response->getBody()->getContents());
    }

    /**
     * @var TokenTypeHintManager|null
     */
    private $tokenTypeHintManager = null;

    private function getTokenTypeHintManager(): TokenTypeHintManager
    {
        if (null === $this->tokenTypeHintManager) {
            $token1 = $this->prophesize(Token::class);
            $token1->getClientId()->willReturn(new ClientId('CLIENT_ID'));

            $token2 = $this->prophesize(Token::class);
            $token2->getClientId()->willReturn(new ClientId('OTHER_CLIENT_ID'));

            $tokenType = $this->prophesize(TokenTypeHint::class);
            $tokenType->find('VALID_TOKEN')->willReturn($token1->reveal());
            $tokenType->find('TOKEN_FOR_ANOTHER_CLIENT')->willReturn($token2->reveal());
            $tokenType->find('UNKNOWN_TOKEN')->willReturn(null);
            $tokenType->hint()->willReturn('foo');
            $tokenType->revoke($token1)->willReturn(null);

            $this->tokenTypeHintManager = new TokenTypeHintManager();
            $this->tokenTypeHintManager->add($tokenType->reveal());
        }

        return $this->tokenTypeHintManager;
    }

    /**
     * @var TokenRevocationGetEndpoint|null
     */
    private $tokenRevocationEndpoint = null;

    private function getTokenRevocationGetEndpoint(): TokenRevocationGetEndpoint
    {
        if (null === $this->tokenRevocationEndpoint) {
            $this->tokenRevocationEndpoint = new TokenRevocationGetEndpoint(
                $this->getTokenTypeHintManager(),
                $this->getResponseFactory(),
                true
            );
        }

        return $this->tokenRevocationEndpoint;
    }

    /**
     * @var ResponseFactory|null
     */
    private $responseFactory = null;

    private function getResponseFactory(): ResponseFactory
    {
        if (null === $this->responseFactory) {
            $this->responseFactory = new DiactorosMessageFactory();
        }

        return $this->responseFactory;
    }

    /**
     * @var Client|null
     */
    private $client = null;

    private function getClient(): Client
    {
        if (null === $this->client) {
            $this->client = new Client(
                new ClientId('CLIENT_ID'),
                new DataBag([]),
                new UserAccountId('USER_ACCOUNT')
            );
        }

        return $this->client;
    }
}
