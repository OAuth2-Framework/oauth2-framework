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

namespace OAuth2Framework\Component\TokenType\Tests\AccessToken;

use Psr\Http\Server\RequestHandlerInterface;
use OAuth2Framework\Component\TokenType\TokenTypeMiddleware;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OAuth2Framework\Component\TokenType\TokenType;
use OAuth2Framework\Component\TokenType\TokenTypeManager;
use PHPUnit\Framework\TestCase;

/**
 * @group TokenTypeMiddleware
 */
class TokenTypeMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function noTokenTypeFoundInTheRequest()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([]);
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
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported token type "bar".
     */
    public function aTokenTypeIsFoundInTheRequestButNotSupported()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
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
    private $tokenTypeMiddleware = null;

    /**
     * @return TokenTypeMiddleware
     */
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
    private $tokenTypeManager = null;

    /**
     * @return TokenTypeManager
     */
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
}
