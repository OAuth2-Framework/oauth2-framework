<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\TokenType;

use InvalidArgumentException;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class TokenTypeParameterCheckerTest extends TestCase
{
    use ProphecyTrait;

    private ?TokenTypeGuesser $tokenTypeGuesser = null;

    protected function setUp(): void
    {
        if (! class_exists(AuthorizationRequest::class)) {
            static::markTestSkipped('Authorization Endpoint not available');
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithNoTokenTypeParameterIsChecked(): void
    {
        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('token_type')
            ->willReturn(false)
            ->shouldBeCalled()
        ;
        $this->getTokenTypeGuesser(true)
            ->find($authorization->reveal())
        ;
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithTokenTypeParameterIsCheckedAndTheTokenTypeIsKnown(): void
    {
        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('token_type')
            ->willReturn(true)
            ->shouldBeCalled()
        ;
        $authorization->getQueryParam('token_type')
            ->willReturn('KnownTokenType')
            ->shouldBeCalled()
        ;
        $this->getTokenTypeGuesser(true)
            ->find($authorization->reveal())
        ;
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithTokenTypeParameterIsCheckedButTheTokenTypeIsUnknown(): void
    {
        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('token_type')
            ->willReturn(true)
            ->shouldBeCalled()
        ;
        $authorization->getQueryParam('token_type')
            ->willReturn('UnknownTokenType')
            ->shouldBeCalled()
        ;

        try {
            $this->getTokenTypeGuesser(true)
                ->find($authorization->reveal())
            ;
            static::fail('Expected exception nt thrown.');
        } catch (InvalidArgumentException $e) {
            static::assertSame('Unsupported token type "UnknownTokenType".', $e->getMessage());
        }
    }

    private function getTokenTypeGuesser(bool $tokenTypeParameterAllowed): TokenTypeGuesser
    {
        if ($this->tokenTypeGuesser === null) {
            $defaultTokenType = $this->prophesize(TokenType::class);
            $anotherTokenType = $this->prophesize(TokenType::class);

            $tokenTypeManager = $this->prophesize(TokenTypeManager::class);
            $tokenTypeManager->get('UnknownTokenType')
                ->willThrow(new InvalidArgumentException('Unsupported token type "UnknownTokenType".'))
            ;
            $tokenTypeManager->get('KnownTokenType')
                ->willReturn($anotherTokenType->reveal())
            ;
            $tokenTypeManager->getDefault()
                ->willReturn($defaultTokenType->reveal())
            ;

            $this->tokenTypeGuesser = new TokenTypeGuesser(
                $tokenTypeManager->reveal(),
                $tokenTypeParameterAllowed
            );
        }

        return $this->tokenTypeGuesser;
    }
}
