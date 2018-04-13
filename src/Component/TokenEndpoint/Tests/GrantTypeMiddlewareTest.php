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

use Psr\Http\Server\RequestHandlerInterface;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpoint
 * @group GrantTypeMiddleware
 */
class GrantTypeMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        self::assertEquals(['foo'], $this->getGrantTypeManager()->list());
        self::assertInstanceOf(GrantType::class, $this->getGrantTypeManager()->get('foo'));
    }

    /**
     * @test
     */
    public function theGrantTypeParameterIsMissing()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getGrantTypeMiddleware()->process($request->reveal(), $handler->reveal());
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
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
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
            'grant_type' => 'bar',
        ]);
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getGrantTypeMiddleware()->process($request->reveal(), $handler->reveal());
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
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
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
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
     * @var null|GrantTypeManager
     */
    private $grantTypeManager = null;

    /**
     * @var null|GrantTypeMiddleware
     */
    private $grantTypeMiddleware = null;

    /**
     * @return GrantTypeManager
     */
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

    /**
     * @return GrantTypeMiddleware
     */
    private function getGrantTypeMiddleware(): GrantTypeMiddleware
    {
        if (null === $this->grantTypeMiddleware) {
            $this->grantTypeMiddleware = new GrantTypeMiddleware(
                $this->getGrantTypeManager()
            );
        }

        return $this->grantTypeMiddleware;
    }
}
