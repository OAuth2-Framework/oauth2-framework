<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\TokenEndpoint;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Middleware\TerminalRequestHandler;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeMiddleware;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class GrantTypeMiddlewareTest extends OAuth2TestCase
{
    private ?GrantTypeMiddleware $grantTypeMiddleware = null;

    /**
     * @test
     */
    public function genericCalls(): void
    {
        static::assertSame([
            'client_credentials',
            'password',
            'implicit',
            'authorization_code',
            'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'refresh_token',
        ], $this->getGrantTypeManager()
            ->list());
    }

    /**
     * @test
     */
    public function theGrantTypeParameterIsMissing(): void
    {
        $request = $this->buildRequest('GET', []);

        try {
            $this->getGrantTypeMiddleware()
                ->process($request, new TerminalRequestHandler(new Psr17Factory()))
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'The "grant_type" parameter is missing.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theGrantTypeIsNotSupported(): void
    {
        $request = $this->buildRequest('GET', [
            'grant_type' => 'bar',
        ]);

        try {
            $this->getGrantTypeMiddleware()
                ->process($request, new TerminalRequestHandler(new Psr17Factory()))
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'The grant type "bar" is not supported by this server.',
            ], $e->getData());
        }
    }

    private function getGrantTypeMiddleware(): GrantTypeMiddleware
    {
        if ($this->grantTypeMiddleware === null) {
            $this->grantTypeMiddleware = new GrantTypeMiddleware($this->getGrantTypeManager());
        }

        return $this->grantTypeMiddleware;
    }
}
