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

namespace OAuth2Framework\Tests\Component\TokenIntrospectionEndpoint;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenIntrospectionEndpoint;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHintManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group TokenIntrospectionEndpoint
 *
 * @internal
 */
final class TokenIntrospectionEndpointTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var null|TokenTypeHintManager
     */
    private $tokenTypeHintManager;

    /**
     * @var null|TokenIntrospectionEndpoint
     */
    private $tokenIntrospectionEndpoint;

    /**
     * @var null|ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var null|ResourceServer
     */
    private $resourceServer;

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
    public function theTokenIntrospectionEndpointReceivesAValidRequest()
    {
        $endpoint = $this->getTokenIntrospectionEndpoint();

        $request = $this->buildRequest(['token' => 'VALID_TOKEN']);
        $request->getAttribute('resource_server')->willReturn($this->getResourceServer());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        static::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        static::assertEquals('{"active":true}', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenIntrospectionEndpointReceivesAValidRequestWithTokenTypeHint()
    {
        $endpoint = $this->getTokenIntrospectionEndpoint();

        $request = $this->buildRequest(['token' => 'VALID_TOKEN', 'token_type_hint' => 'foo']);
        $request->getAttribute('resource_server')->willReturn($this->getResourceServer());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        static::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        static::assertEquals('{"active":true}', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenIntrospectionEndpointReceivesARequestWithAnUnsupportedTokenHint()
    {
        $endpoint = $this->getTokenIntrospectionEndpoint();

        $request = $this->buildRequest(['token' => 'VALID_TOKEN', 'token_type_hint' => 'bar']);
        $request->getAttribute('resource_server')->willReturn($this->getResourceServer());

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        static::assertEquals(400, $response->getStatusCode());
        $response->getBody()->rewind();
        static::assertEquals('{"error":"unsupported_token_type","error_description":"The token type hint \"bar\" is not supported. Please use one of the following values: foo."}', $response->getBody()->getContents());
    }

    private function getTokenTypeHintManager(): TokenTypeHintManager
    {
        if (null === $this->tokenTypeHintManager) {
            $token = $this->prophesize(AccessToken::class);
            $token->getResourceServerId()->willReturn(new ResourceServerId('RESOURCE_SERVER_ID'));

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

    private function getResponseFactory(): ResponseFactoryInterface
    {
        if (null === $this->responseFactory) {
            $this->responseFactory = new Psr17Factory();
        }

        return $this->responseFactory;
    }

    private function getResourceServer(): ResourceServer
    {
        if (null === $this->resourceServer) {
            $this->resourceServer = $this->prophesize(ResourceServer::class);
            $this->resourceServer->getResourceServerId()->willReturn(new ResourceServerId('RESOURCE_SERVER_ID'));
        }

        return $this->resourceServer->reveal();
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()->willReturn(http_build_query($data));
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')->willReturn(true);
        $request->getHeader('Content-Type')->willReturn(['application/x-www-form-urlencoded']);
        $request->getBody()->willReturn($body->reveal());
        $request->getParsedBody()->willReturn([]);

        return $request;
    }
}
