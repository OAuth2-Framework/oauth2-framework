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

namespace OAuth2Framework\Tests\Component\TokenRevocationEndpoint;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationGetEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHintManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group TokenRevocationEndpoint
 *
 * @internal
 */
final class TokenRevocationGetEndpointTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var null|TokenTypeHintManager
     */
    private $tokenTypeHintManager;

    /**
     * @var null|TokenRevocationGetEndpoint
     */
    private $tokenRevocationEndpoint;

    /**
     * @var null|ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var null|Client
     */
    private $client;

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

    private function getTokenTypeHintManager(): TokenTypeHintManager
    {
        if (null === $this->tokenTypeHintManager) {
            $token1 = $this->prophesize(AccessToken::class);
            $token1->getClientId()->willReturn(new ClientId('CLIENT_ID'));

            $token2 = $this->prophesize(AccessToken::class);
            $token2->getClientId()->willReturn(new ClientId('OTHER_CLIENT_ID'));

            $tokenType = $this->prophesize(TokenTypeHint::class);
            $tokenType->find('VALID_TOKEN')->willReturn($token1->reveal());
            $tokenType->find('TOKEN_FOR_ANOTHER_CLIENT')->willReturn($token2->reveal());
            $tokenType->find('UNKNOWN_TOKEN')->willReturn(null);
            $tokenType->hint()->willReturn('foo');
            $tokenType->revoke($token1)->will(function () {});

            $this->tokenTypeHintManager = new TokenTypeHintManager();
            $this->tokenTypeHintManager->add($tokenType->reveal());
        }

        return $this->tokenTypeHintManager;
    }

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

    private function getResponseFactory(): ResponseFactoryInterface
    {
        if (null === $this->responseFactory) {
            $this->responseFactory = new Psr17Factory();
        }

        return $this->responseFactory;
    }

    private function getClient(): Client
    {
        if (null === $this->client) {
            $client = $this->prophesize(Client::class);
            $client->isPublic()->willReturn(false);
            $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT'));
            $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
            $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));

            $this->client = $client->reveal();
        }

        return $this->client;
    }
}
