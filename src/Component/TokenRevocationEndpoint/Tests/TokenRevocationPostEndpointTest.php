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
use Psr\Http\Server\RequestHandlerInterface;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationPostEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHintManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenRevocationEndpoint
 */
class TokenRevocationPostEndpointTest extends TestCase
{
    /**
     * @test
     */
    public function aTokenTypeHintManagerCanHandleTokenTypeHints()
    {
        self::assertNotEmpty($this->getTokenTypeHintManager()->getTokenTypeHints());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesAValidPostRequest()
    {
        $endpoint = $this->getTokenRevocationPostEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['token' => 'VALID_TOKEN']);
        $request->getAttribute('client')->willReturn($this->getClient());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        self::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        self::assertEquals('', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesAValidPostRequestWithTokenTypeHint()
    {
        $endpoint = $this->getTokenRevocationPostEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['token' => 'VALID_TOKEN', 'token_type_hint' => 'foo']);
        $request->getAttribute('client')->willReturn($this->getClient());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        self::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        self::assertEquals('', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesARequestWithAnUnsupportedTokenHint()
    {
        $endpoint = $this->getTokenRevocationPostEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['token' => 'VALID_TOKEN', 'token_type_hint' => 'bar']);
        $request->getAttribute('client')->willReturn($this->getClient());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        self::assertEquals(400, $response->getStatusCode());
        $response->getBody()->rewind();
        self::assertEquals('{"error":"unsupported_token_type","error_description":"The token type hint \"bar\" is not supported. Please use one of the following values: foo."}', $response->getBody()->getContents());
    }

    /**
     * @var TokenTypeHintManager|null
     */
    private $tokenTypeHintManager = null;

    /**
     * @return TokenTypeHintManager
     */
    private function getTokenTypeHintManager(): TokenTypeHintManager
    {
        if (null === $this->tokenTypeHintManager) {
            $token = $this->prophesize(Token::class);
            $token->getClientId()->willReturn(ClientId::create('CLIENT_ID'));

            $tokenType = $this->prophesize(TokenTypeHint::class);
            $tokenType->find('VALID_TOKEN')->willReturn($token->reveal());
            $tokenType->find('BAD_TOKEN')->willReturn(null);
            $tokenType->hint()->willReturn('foo');
            $tokenType->revoke($token)->willReturn(null);

            $this->tokenTypeHintManager = new TokenTypeHintManager();
            $this->tokenTypeHintManager->add($tokenType->reveal());
        }

        return $this->tokenTypeHintManager;
    }

    /**
     * @var TokenRevocationPostEndpoint|null
     */
    private $tokenRevocationEndpoint = null;

    /**
     * @return TokenRevocationPostEndpoint
     */
    private function getTokenRevocationPostEndpoint(): TokenRevocationPostEndpoint
    {
        if (null === $this->tokenRevocationEndpoint) {
            $this->tokenRevocationEndpoint = new TokenRevocationPostEndpoint(
                $this->getTokenTypeHintManager(),
                $this->getResponseFactory()
            );
        }

        return $this->tokenRevocationEndpoint;
    }

    /**
     * @var ResponseFactory|null
     */
    private $responseFactory = null;

    /**
     * @return ResponseFactory
     */
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

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        if (null === $this->client) {
            $this->client = Client::createEmpty();
            $this->client = $this->client->create(
                ClientId::create('CLIENT_ID'),
                DataBag::create([]),
                UserAccountId::create('USER_ACCOUNT')
            );
        }

        return $this->client;
    }
}
