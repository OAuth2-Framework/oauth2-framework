<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\TokenRevocationEndpoint;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Middleware\TerminalRequestHandler;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class TokenRevocationPostEndpointTest extends OAuth2TestCase
{
    private ?object $client = null;

    /**
     * @test
     */
    public function aTokenTypeHintManagerCanHandleTokenTypeHints(): void
    {
        static::assertNotEmpty($this->getTokenTypeHintManager()->getTokenTypeHints());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesAValidPostRequest(): void
    {
        $endpoint = $this->getTokenRevocationPostEndpoint();

        $request = $this->buildRequest('POST', [
            'token' => 'VALID_TOKEN',
        ]);
        $request = $request->withAttribute('client', $this->getClient());

        $response = $endpoint->process($request, new TerminalRequestHandler(new Psr17Factory()));

        static::assertSame(200, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertSame('', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesAValidPostRequestWithTokenTypeHint(): void
    {
        $endpoint = $this->getTokenRevocationPostEndpoint();

        $request = $this->buildRequest('POST', [
            'token' => 'VALID_TOKEN',
            'token_type_hint' => 'access_token',
        ]);
        $request = $request->withAttribute('client', $this->getClient());

        $response = $endpoint->process($request, new TerminalRequestHandler(new Psr17Factory()));

        static::assertSame(200, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertSame('', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesARequestWithAnUnsupportedTokenHint(): void
    {
        $endpoint = $this->getTokenRevocationPostEndpoint();

        $request = $this->buildRequest('POST', [
            'token' => 'VALID_TOKEN',
            'token_type_hint' => 'bar',
        ]);
        $request = $request->withAttribute('client', $this->getClient());

        $response = $endpoint->process($request, new TerminalRequestHandler(new Psr17Factory()));

        static::assertSame(400, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertSame(
            '{"error":"unsupported_token_type","error_description":"The token type hint \"bar\" is not supported. Please use one of the following values: access_token, refresh_token."}',
            $response->getBody()
                ->getContents()
        );
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = Client::create(
                ClientId::create('CLIENT_ID'),
                DataBag::create(),
                UserAccountId::create('USER_ACCOUNT')
            );
        }

        return $this->client;
    }
}
