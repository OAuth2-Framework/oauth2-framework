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

namespace OAuth2Framework\Component\TokenIntrospectionEndpoint\Tests;

use Http\Message\MessageFactory\DiactorosMessageFactory;
use Http\Message\ResponseFactory;
use Psr\Http\Server\RequestHandlerInterface;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenIntrospectionEndpoint;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHintManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenIntrospectionEndpoint
 */
final class TokenIntrospectionEndpointTest extends TestCase
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
    public function theTokenIntrospectionEndpointReceivesAValidRequest()
    {
        $endpoint = $this->getTokenIntrospectionEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['token' => 'VALID_TOKEN']);
        $request->getAttribute('resource_server')->willReturn($this->getResourceServer());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        self::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        self::assertEquals('{"active":true}', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenIntrospectionEndpointReceivesAValidRequestWithTokenTypeHint()
    {
        $endpoint = $this->getTokenIntrospectionEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['token' => 'VALID_TOKEN', 'token_type_hint' => 'foo']);
        $request->getAttribute('resource_server')->willReturn($this->getResourceServer());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        self::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        self::assertEquals('{"active":true}', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenIntrospectionEndpointReceivesARequestWithAnUnsupportedTokenHint()
    {
        $endpoint = $this->getTokenIntrospectionEndpoint();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['token' => 'VALID_TOKEN', 'token_type_hint' => 'bar']);
        $request->getAttribute('resource_server')->willReturn($this->getResourceServer());

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
            $token->getResourceServerId()->willReturn(ResourceServerId::create('RESOURCE_SERVER_ID'));

            $tokenType = $this->prophesize(TokenTypeHint::class);
            $tokenType->find('VALID_TOKEN')->willReturn($token->reveal());
            $tokenType->find('BAD_TOKEN')->willReturn(null);
            $tokenType->hint()->willReturn('foo');
            $tokenType->introspect($token)->willReturn(['active' => true]);

            $this->tokenTypeHintManager = new TokenTypeHintManager();
            $this->tokenTypeHintManager->add($tokenType->reveal());
        }

        return $this->tokenTypeHintManager;
    }

    /**
     * @var TokenIntrospectionEndpoint|null
     */
    private $tokenIntrospectionEndpoint = null;

    /**
     * @return TokenIntrospectionEndpoint
     */
    private function getTokenIntrospectionEndpoint(): TokenIntrospectionEndpoint
    {
        if (null === $this->tokenIntrospectionEndpoint) {
            $this->tokenIntrospectionEndpoint = new TokenIntrospectionEndpoint(
                $this->getTokenTypeHintManager(),
                $this->getResponseFactory()
            );
        }

        return $this->tokenIntrospectionEndpoint;
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
     * @var ResourceServer|null
     */
    private $resourceServer = null;

    /**
     * @return ResourceServer
     */
    private function getResourceServer(): ResourceServer
    {
        if (null === $this->resourceServer) {
            $this->resourceServer = $this->prophesize(ResourceServer::class);
            $this->resourceServer->getResourceServerId()->willReturn(ResourceServerId::create('RESOURCE_SERVER_ID'));
        }

        return $this->resourceServer->reveal();
    }
}
