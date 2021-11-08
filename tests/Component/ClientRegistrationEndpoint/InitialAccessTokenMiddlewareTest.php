<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRegistrationEndpoint;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Middleware\TerminalRequestHandler;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class InitialAccessTokenMiddlewareTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function theInitialAccessTokenIsNotKnown(): void
    {
        $request = $this->buildRequest('GET', [], [
            'AUTHORIZATION' => 'Bearer BAD_INITIAL_ACCESS_TOKEN_ID',
        ]);

        try {
            $this->getInitialAccessTokenMiddleware()
                ->process($request, new TerminalRequestHandler(new Psr17Factory()))
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Initial Access Token is missing or invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsRevoked(): void
    {
        $request = $this->buildRequest('GET', [], [
            'AUTHORIZATION' => 'Bearer REVOKED_INITIAL_ACCESS_TOKEN_ID',
        ]);

        try {
            $this->getInitialAccessTokenMiddleware()
                ->process($request, new TerminalRequestHandler(new Psr17Factory()))
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Initial Access Token is missing or invalid.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theInitialAccessTokenExpired(): void
    {
        $request = $this->buildRequest('GET', [], [
            'AUTHORIZATION' => 'Bearer EXPIRED_INITIAL_ACCESS_TOKEN_ID',
        ]);

        try {
            $this->getInitialAccessTokenMiddleware()
                ->process($request, new TerminalRequestHandler(new Psr17Factory()))
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Initial Access Token expired.',
            ], $e->getData());
        }
    }
}
