<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\TokenType;

use InvalidArgumentException;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class TokenTypeTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function aTokenTypeManagerCanHandleTokenTypes(): void
    {
        static::assertFalse($this->getTokenTypeManager()->has('foo'));
        static::assertTrue($this->getTokenTypeManager()->has('Bearer'));
        static::assertNotEmpty($this->getTokenTypeManager()->all());
        static::assertSame(['Bearer realm="Realm",foo="bar",OOO=123'], $this->getTokenTypeManager()->getSchemes([
            'foo' => 'bar',
            'OOO' => 123,
        ]));
        static::assertSame(['Bearer realm="Realm"'], $this->getTokenTypeManager()->getSchemes());
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
    public function aTokenTypeManagerCanFindATokenInARequestHeader(): void
    {
        $additionalCredentialValues = [];

        $request = $this->buildRequest(headers: [
            'AUTHORIZATION' => 'Bearer TOKEN',
        ]);

        static::assertSame(
            'TOKEN',
            $this->getTokenTypeManager()
                ->findToken($request, $additionalCredentialValues, $tokenType)
        );
    }

    /**
     * @test
     */
    public function aTokenTypeManagerCanFindATokenInARequestBody(): void
    {
        $additionalCredentialValues = [];

        $request = $this->buildRequest(data: [
            'access_token' => 'TOKEN',
        ]);

        static::assertSame(
            'TOKEN',
            $this->getTokenTypeManager()
                ->findToken($request, $additionalCredentialValues, $tokenType)
        );
    }

    /**
     * @test
     */
    public function aTokenTypeManagerCanFindATokenInAQueryString(): void
    {
        $additionalCredentialValues = [];

        $request = $this->buildRequest(queryParameters: [
            'access_token' => 'TOKEN',
        ]);

        static::assertSame(
            'TOKEN',
            $this->getTokenTypeManager()
                ->findToken($request, $additionalCredentialValues, $tokenType)
        );
    }
}
