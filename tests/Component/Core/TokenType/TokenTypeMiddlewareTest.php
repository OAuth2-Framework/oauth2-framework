<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\TokenType;

use InvalidArgumentException;
use OAuth2Framework\Component\Core\Middleware\TerminalRequestHandler;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class TokenTypeMiddlewareTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function aTokenTypeIsFoundInTheRequestButNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported token type "bar".');
        $request = $this->buildRequest('GET', [
            'token_type' => 'bar',
        ]);

        $this->getTokenTypeMiddleware()
            ->process($request, new TerminalRequestHandler())
        ;
    }
}
