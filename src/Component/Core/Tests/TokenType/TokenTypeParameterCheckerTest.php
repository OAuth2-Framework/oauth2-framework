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

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Core\TokenType\TokenTypeParameterChecker;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group TokenTypeParameterCheck
 */
class TokenTypeParameterCheckerTest extends TestCase
{
    /**
     * @inheritdoc}
     */
    protected function setUp()
    {
        if (!class_exists(Authorization::class)) {
            $this->markTestSkipped('Authorization Endpoint not available');
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithNoTokenTypeParameterIsChecked()
    {
        $authorization = $this->prophesize(Authorization::class);
        $authorization->hasQueryParam('token_type')->willReturn(false)->shouldBeCalled();
        $authorization
            ->withTokenType(Argument::type(TokenType::class))
            ->shouldBeCalled()
            ->willReturn($authorization);
        $this->getTokenTypeParameterChecker(true)->check(
            $authorization->reveal()
        );
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithTokenTypeParameterIsCheckedAndTheTokenTypeIsKnown()
    {
        $authorization = $this->prophesize(Authorization::class);
        $authorization->hasQueryParam('token_type')->willReturn(true)->shouldBeCalled();
        $authorization->getQueryParam('token_type')->willReturn('KnownTokenType')->shouldBeCalled();
        $authorization
            ->withTokenType(Argument::type(TokenType::class))
            ->shouldBeCalled()
            ->willReturn($authorization);
        $this->getTokenTypeParameterChecker(true)->check(
            $authorization->reveal()
        );
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithTokenTypeParameterIsCheckedButTheTokenTypeIsUnknown()
    {
        $authorization = $this->prophesize(Authorization::class);
        $authorization->hasQueryParam('token_type')->willReturn(true)->shouldBeCalled();
        $authorization->getQueryParam('token_type')->willReturn('UnknownTokenType')->shouldBeCalled();

        try {
            $this->getTokenTypeParameterChecker(true)->check(
                $authorization->reveal()
            );
            $this->fail('Expected exception nt thrown.');
        } catch (OAuth2AuthorizationException $e) {
            self::assertEquals('invalid_request', $e->getMessage());
            self::assertEquals('Unsupported token type "UnknownTokenType".', $e->getErrorDescription());
            self::assertEquals(400, $e->getCode());
        }
    }

    /**
     * @var TokenTypeParameterChecker|null
     */
    private $tokenTypeParameterChecker;

    /**
     * @param bool $tokenTypeParameterAllowed
     *
     * @return TokenTypeParameterChecker
     */
    private function getTokenTypeParameterChecker(bool $tokenTypeParameterAllowed): TokenTypeParameterChecker
    {
        if (null === $this->tokenTypeParameterChecker) {
            $defaultTokenType = $this->prophesize(TokenType::class);
            $anotherTokenType = $this->prophesize(TokenType::class);

            $tokenTypeManager = $this->prophesize(TokenTypeManager::class);
            $tokenTypeManager->get('UnknownTokenType')->willThrow(new \InvalidArgumentException('Unsupported token type "UnknownTokenType".'));
            $tokenTypeManager->get('KnownTokenType')->willReturn($anotherTokenType->reveal());
            $tokenTypeManager->getDefault()->willReturn($defaultTokenType->reveal());

            $this->tokenTypeParameterChecker = new TokenTypeParameterChecker(
                $tokenTypeManager->reveal(),
                $tokenTypeParameterAllowed
            );
        }

        return $this->tokenTypeParameterChecker;
    }
}