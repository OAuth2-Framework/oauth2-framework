<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\TokenRevocationEndpoint;

use DateTimeImmutable;
use Nyholm\Psr7\ServerRequest;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Middleware\TerminalRequestHandler;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Tests\TestBundle\Entity\AccessToken;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class TokenRevocationGetEndpointTest extends OAuth2TestCase
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
    public function theTokenRevocationEndpointReceivesAValidGetRequest(): void
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();
        $request = new ServerRequest('GET', '/');
        $request = $request
            ->withQueryParams([
                'token' => 'VALID_TOKEN',
            ])
            ->withAttribute('client', $this->getClient())
        ;

        $response = $endpoint->process($request, new TerminalRequestHandler());

        static::assertSame(200, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertSame('', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesAValidGetRequestWithTokenTypeHint(): void
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();
        $this->getAccessTokenRepository()
            ->save(AccessToken::create(
                AccessTokenId::create('VALID_TOKEN'),
                ClientId::create('CLIENT_ID'),
                ClientId::create('CLIENT_ID'),
                new DateTimeImmutable('now +1 day'),
                DataBag::create(),
                DataBag::create(),
                ResourceServerId::create('RESOURCE_SERVER_ID'),
            ))
        ;

        $request = new ServerRequest('GET', '/');
        $request = $request
            ->withQueryParams([
                'token' => 'VALID_TOKEN',
                'token_type_hint' => 'access_token',
            ])
            ->withAttribute('client', $this->getClient())
        ;

        $response = $endpoint->process($request, new TerminalRequestHandler());

        static::assertSame(200, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertSame('', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesAValidGetRequestWithCallback(): void
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();
        $this->getRefreshTokenRepository()
            ->save(RefreshToken::create(
                RefreshTokenId::create('VALID_TOKEN'),
                ClientId::create('CLIENT_ID'),
                ClientId::create('CLIENT_ID'),
                new DateTimeImmutable('now +1 day'),
                DataBag::create(),
                DataBag::create(),
                ResourceServerId::create('RESOURCE_SERVER_ID'),
            ))
        ;

        $request = new ServerRequest('GET', '/');
        $request = $request
            ->withQueryParams([
                'token' => 'VALID_TOKEN',
                'callback' => 'callThisFunctionPlease',
            ])
            ->withAttribute('client', $this->getClient())
        ;

        $response = $endpoint->process($request, new TerminalRequestHandler());

        static::assertSame(200, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertSame('callThisFunctionPlease()', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenDoesNotExistAndCannotBeRevoked(): void
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();
        $request = new ServerRequest('GET', '/');
        $request = $request
            ->withQueryParams([
                'token' => 'UNKNOWN_TOKEN',
                'callback' => 'callThisFunctionPlease',
            ])
            ->withAttribute('client', $this->getClient())
        ;

        $response = $endpoint->process($request, new TerminalRequestHandler());

        static::assertSame(200, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertSame('callThisFunctionPlease()', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesFromAnotherClient(): void
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();
        $request = new ServerRequest('GET', '/');
        $request = $request
            ->withQueryParams([
                'token' => 'TOKEN_FOR_ANOTHER_CLIENT',
                'callback' => 'callThisFunctionPlease',
            ])
            ->withAttribute('client', $this->getClient())
        ;

        $response = $endpoint->process($request, new TerminalRequestHandler());

        static::assertSame(200, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertSame('callThisFunctionPlease()', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theTokenRevocationEndpointReceivesARequestWithAnUnsupportedTokenHint(): void
    {
        $endpoint = $this->getTokenRevocationGetEndpoint();
        $request = new ServerRequest('GET', '/');
        $request = $request
            ->withQueryParams([
                'token' => 'VALID_TOKEN',
                'token_type_hint' => 'bar',
            ])
            ->withAttribute('client', $this->getClient())
        ;

        $response = $endpoint->process($request, new TerminalRequestHandler());

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
