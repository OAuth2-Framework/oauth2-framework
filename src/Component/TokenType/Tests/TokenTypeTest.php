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

use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use OAuth2Framework\Component\TokenType\TokenType;
use OAuth2Framework\Component\TokenType\TokenTypeManager;
use PHPUnit\Framework\TestCase;

/**
 * @group TokenType
 */
class TokenTypeTest extends TestCase
{
    /**
     * @test
     */
    public function aTokenTypeManagerCanHandleTokenTypes()
    {
        self::assertTrue($this->getTokenTypeManager()->has('foo'));
        self::assertInstanceOf(TokenType::class, $this->getTokenTypeManager()->get('foo'));
        self::assertNotEmpty($this->getTokenTypeManager()->all());
        self::assertInstanceOf(TokenType::class, $this->getTokenTypeManager()->getDefault());
        self::assertEquals(['FOO foo="bar",OOO=123'], $this->getTokenTypeManager()->getSchemes(['all' => ['foo' => 'bar', 'OOO' => 123]]));
        self::assertEquals(['FOO'], $this->getTokenTypeManager()->getSchemes());
    }

    /**
     * @test
     */
    public function theRequestedTokenTypeDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported token type "bar".');
        $this->getTokenTypeManager()->get('bar');
    }

    /**
     * @test
     */
    public function aTokenTypeManagerCanFindATokenInARequest()
    {
        $additionalCredentialValues = [];
        $tokenType = null;
        $request = $this->prophesize(ServerRequestInterface::class);

        self::assertEquals('__--TOKEN--__', $this->getTokenTypeManager()->findToken($request->reveal(), $additionalCredentialValues, $tokenType));
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
