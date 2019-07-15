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

namespace OAuth2Framework\Component\Core\Tests\TokenType;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use PHPUnit\Framework\TestCase;

/**
 * @group TokenTypeParameterCheck
 *
 * @internal
 * @coversNothing
 */
final class TokenTypeParameterCheckerTest extends TestCase
{
    /**
     * @var null|TokenTypeGuesser
     */
    private $tokenTypeGuesser;

    protected function setUp(): void
    {
        if (!class_exists(AuthorizationRequest::class)) {
            static::markTestSkipped('Authorization Endpoint not available');
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithNoTokenTypeParameterIsChecked()
    {
        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('token_type')->willReturn(false)->shouldBeCalled();
        $this->getTokenTypeGuesser(true)->find(
            $authorization->reveal()
        );
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithTokenTypeParameterIsCheckedAndTheTokenTypeIsKnown()
    {
        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('token_type')->willReturn(true)->shouldBeCalled();
        $authorization->getQueryParam('token_type')->willReturn('KnownTokenType')->shouldBeCalled();
        $this->getTokenTypeGuesser(true)->find(
            $authorization->reveal()
        );
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithTokenTypeParameterIsCheckedButTheTokenTypeIsUnknown()
    {
        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('token_type')->willReturn(true)->shouldBeCalled();
        $authorization->getQueryParam('token_type')->willReturn('UnknownTokenType')->shouldBeCalled();

        try {
            $this->getTokenTypeGuesser(true)->find(
                $authorization->reveal()
            );
            static::fail('Expected exception nt thrown.');
        } catch (\InvalidArgumentException $e) {
            static::assertEquals('Unsupported token type "UnknownTokenType".', $e->getMessage());
        }
    }

    private function getTokenTypeGuesser(bool $tokenTypeParameterAllowed): TokenTypeGuesser
    {
        if (null === $this->tokenTypeGuesser) {
            $defaultTokenType = $this->prophesize(TokenType::class);
            $anotherTokenType = $this->prophesize(TokenType::class);

            $tokenTypeManager = $this->prophesize(TokenTypeManager::class);
            $tokenTypeManager->get('UnknownTokenType')->willThrow(new \InvalidArgumentException('Unsupported token type "UnknownTokenType".'));
            $tokenTypeManager->get('KnownTokenType')->willReturn($anotherTokenType->reveal());
            $tokenTypeManager->getDefault()->willReturn($defaultTokenType->reveal());

            $this->tokenTypeGuesser = new TokenTypeGuesser(
                $tokenTypeManager->reveal(),
                $tokenTypeParameterAllowed
            );
        }

        return $this->tokenTypeGuesser;
    }
}
