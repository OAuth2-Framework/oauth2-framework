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

namespace OAuth2Framework\Component\Core\Tests\TokenType;

use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Core\TokenType\TokenTypeMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group TokenTypeMiddleware
 */
final class TokenTypeMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function noTokenTypeFoundInTheRequest()
    {
        $request = $this->buildRequest([]);
        $request->withAttribute('token_type', Argument::type(TokenType::class))->willReturn($request)->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn($response->reveal());

        $this->getTokenTypeMiddleware()->process($request->reveal(), $handler->reveal());
    }

    /**
     * @test
     */
    public function aTokenTypeIsFoundInTheRequest()
    {
        $request = $this->buildRequest([
            'token_type' => 'foo',
        ]);
        $request->withAttribute('token_type', Argument::type(TokenType::class))->willReturn($request)->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn($response->reveal());

        $this->getTokenTypeMiddleware()->process($request->reveal(), $handler->reveal());
    }

    /**
     * @test
     */
    public function aTokenTypeIsFoundInTheRequestButNotSupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported token type "bar".');
        $request = $this->buildRequest([
            'token_type' => 'bar',
        ]);

        $response = $this->prophesize(ResponseInterface::class);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn($response->reveal());

        $this->getTokenTypeMiddleware()->process($request->reveal(), $handler->reveal());
    }

    /**
     * @var TokenTypeMiddleware|null
     */
    private $tokenTypeMiddleware;

    private function getTokenTypeMiddleware(): TokenTypeMiddleware
    {
        if (null === $this->tokenTypeMiddleware) {
            $this->tokenTypeMiddleware = new TokenTypeMiddleware(
                $this->getTokenTypeManager(),
                true
            );
        }

        return $this->tokenTypeMiddleware;
    }

    /**
     * @var TokenTypeManager|null
     */
    private $tokenTypeManager;

    private function getTokenTypeManager(): TokenTypeManager
    {
        if (null === $this->tokenTypeManager) {
            $tokenType = $this->prophesize(TokenType::class);
            $tokenType->name()->willReturn('foo');
            $tokenType->getScheme()->willReturn('FOO');
            $tokenType->find(Argument::any(), Argument::any(), Argument::any())->willReturn('__--TOKEN--__');

            $this->tokenTypeManager = new TokenTypeManager();
            $this->tokenTypeManager->add($tokenType->reveal());
        }

        return $this->tokenTypeManager;
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()->willReturn(\http_build_query($data));
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')->willReturn(true);
        $request->getHeader('Content-Type')->willReturn(['application/x-www-form-urlencoded']);
        $request->getBody()->willReturn($body->reveal());
        $request->getParsedBody()->willReturn([]);

        return $request;
    }
}
