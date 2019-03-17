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

namespace OAuth2Framework\Component\TokenEndpoint\Tests;

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group TokenEndpoint
 * @group GrantTypeMiddleware
 */
final class GrantTypeMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        static::assertEquals(['foo'], $this->getGrantTypeManager()->list());
        static::assertInstanceOf(GrantType::class, $this->getGrantTypeManager()->get('foo'));
    }

    /**
     * @test
     */
    public function theGrantTypeParameterIsMissing()
    {
        $request = $this->buildRequest([]);
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getGrantTypeMiddleware()->process($request->reveal(), $handler->reveal());
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'The "grant_type" parameter is missing.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theGrantTypeIsNotSupported()
    {
        $request = $this->buildRequest([
            'grant_type' => 'bar',
        ]);
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getGrantTypeMiddleware()->process($request->reveal(), $handler->reveal());
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'The grant type "bar" is not supported by this server.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theGrantTypeIsFoundAndAssociatedToTheRequest()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->buildRequest([
            'grant_type' => 'foo',
        ]);
        $request->withAttribute('grant_type', Argument::type(GrantType::class))
            ->shouldBeCalled()
            ->willReturn($request)
        ;
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldBeCalled()
            ->willReturn($response->reveal());

        $this->getGrantTypeMiddleware()->process($request->reveal(), $handler->reveal());
    }

    /**
     * @var GrantTypeManager|null
     */
    private $grantTypeManager;

    /**
     * @var GrantTypeMiddleware|null
     */
    private $grantTypeMiddleware;

    private function getGrantTypeManager(): GrantTypeManager
    {
        if (null === $this->grantTypeManager) {
            $this->grantTypeManager = new GrantTypeManager();
            $grantType = $this->prophesize(GrantType::class);
            $grantType->name()->willReturn('foo');

            $this->grantTypeManager->add($grantType->reveal());
        }

        return $this->grantTypeManager;
    }

    private function getGrantTypeMiddleware(): GrantTypeMiddleware
    {
        if (null === $this->grantTypeMiddleware) {
            $this->grantTypeMiddleware = new GrantTypeMiddleware(
                $this->getGrantTypeManager()
            );
        }

        return $this->grantTypeMiddleware;
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
