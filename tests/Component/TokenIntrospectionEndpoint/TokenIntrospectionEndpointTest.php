<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\TokenIntrospectionEndpoint;

use DateTimeImmutable;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Middleware\TerminalRequestHandler;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenIntrospectionEndpoint;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Tests\TestBundle\Entity\AccessToken;
use OAuth2Framework\Tests\TestBundle\Entity\ResourceServer;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * @internal
 */
final class TokenIntrospectionEndpointTest extends OAuth2TestCase
{
    private ?TokenIntrospectionEndpoint $tokenIntrospectionEndpoint = null;

    private ?Psr17Factory $responseFactory = null;

    private ?ResourceServer $resourceServer = null;

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
    public function theTokenIntrospectionEndpointReceivesAValidRequest(): void
    {
        $endpoint = $this->getTokenIntrospectionEndpoint();
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

        $request = $this->buildRequest('GET', [
            'token' => 'VALID_TOKEN',
        ]);
        $request = $request
            ->withAttribute('resource_server', $this->getResourceServer())
        ;

        $response = $endpoint->process($request, new TerminalRequestHandler(new Psr17Factory()));

        static::assertSame(200, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertMatchesRegularExpression(
            '/^{"active":true,"client_id":"CLIENT_ID","resource_owner":"CLIENT_ID","expires_in":\d+}$/',
            $response->getBody()
                ->getContents()
        );
    }

    /**
     * @test
     */
    public function theTokenIntrospectionEndpointReceivesAValidRequestWithTokenTypeHint(): void
    {
        $endpoint = $this->getTokenIntrospectionEndpoint();
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

        $request = $this->buildRequest('GET', [
            'token' => 'VALID_TOKEN',
            'token_type_hint' => 'access_token',
        ]);
        $request = $request
            ->withAttribute('resource_server', $this->getResourceServer())
        ;

        $response = $endpoint->process($request, new TerminalRequestHandler(new Psr17Factory()));

        static::assertSame(200, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertMatchesRegularExpression(
            '/^{"active":true,"client_id":"CLIENT_ID","resource_owner":"CLIENT_ID","expires_in":\d+}$/',
            $response->getBody()
                ->getContents()
        );
    }

    /**
     * @test
     */
    public function theTokenIntrospectionEndpointReceivesARequestWithAnUnsupportedTokenHint(): void
    {
        $endpoint = $this->getTokenIntrospectionEndpoint();

        $request = $this->buildRequest('GET', [
            'token' => 'VALID_TOKEN',
            'token_type_hint' => 'bar',
        ]);
        $request = $request
            ->withAttribute('resource_server', $this->getResourceServer())
        ;

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

    private function getTokenIntrospectionEndpoint(): TokenIntrospectionEndpoint
    {
        if ($this->tokenIntrospectionEndpoint === null) {
            $this->tokenIntrospectionEndpoint = TokenIntrospectionEndpoint::create(
                $this->getTokenIntrospectionTypeHintManager(),
                $this->getResponseFactory()
            );
        }

        return $this->tokenIntrospectionEndpoint;
    }

    private function getResponseFactory(): ResponseFactoryInterface
    {
        if ($this->responseFactory === null) {
            $this->responseFactory = new Psr17Factory();
        }

        return $this->responseFactory;
    }

    private function getResourceServer(): ResourceServer
    {
        if ($this->resourceServer === null) {
            $this->resourceServer = ResourceServer::create(ResourceServerId::create('RESOURCE_SERVER_ID'));
        }

        return $this->resourceServer;
    }
}
