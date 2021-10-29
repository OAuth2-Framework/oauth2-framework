<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\TokenType;

use InvalidArgumentException;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
final class TokenTypeTest extends TestCase
{
    use ProphecyTrait;

    private ?TokenTypeManager $tokenTypeManager = null;

    /**
     * @test
     */
    public function aTokenTypeManagerCanHandleTokenTypes(): void
    {
        static::assertTrue($this->getTokenTypeManager()->has('foo'));
        static::assertInstanceOf(TokenType::class, $this->getTokenTypeManager()->get('foo'));
        static::assertNotEmpty($this->getTokenTypeManager()->all());
        static::assertInstanceOf(TokenType::class, $this->getTokenTypeManager()->getDefault());
        static::assertSame(['FOO foo="bar",OOO=123'], $this->getTokenTypeManager()->getSchemes([
            'foo' => 'bar',
            'OOO' => 123,
        ]));
        static::assertSame(['FOO'], $this->getTokenTypeManager()->getSchemes());
    }

    /**
     * @test
     */
    public function theRequestedTokenTypeDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported token type "bar".');
        $this->getTokenTypeManager()
            ->get('bar')
        ;
    }

    /**
     * @test
     */
    public function aTokenTypeManagerCanFindATokenInARequest(): void
    {
        $additionalCredentialValues = [];

        $request = $this->prophesize(ServerRequestInterface::class);

        static::assertSame(
            '__--TOKEN--__',
            $this->getTokenTypeManager()
                ->findToken($request->reveal(), $additionalCredentialValues, $tokenType)
        );
    }

    private function getTokenTypeManager(): TokenTypeManager
    {
        if ($this->tokenTypeManager === null) {
            $tokenType = $this->prophesize(TokenType::class);
            $tokenType->name()
                ->willReturn('foo')
            ;
            $tokenType->getScheme()
                ->willReturn('FOO')
            ;
            $tokenType->find(Argument::any(), Argument::any(), Argument::any())->willReturn('__--TOKEN--__');

            $this->tokenTypeManager = new TokenTypeManager();
            $this->tokenTypeManager->add($tokenType->reveal());
        }

        return $this->tokenTypeManager;
    }
}
