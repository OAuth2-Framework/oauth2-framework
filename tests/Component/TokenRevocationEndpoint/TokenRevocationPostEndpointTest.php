<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\TokenRevocationEndpoint;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationPostEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHintManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
final class TokenRevocationPostEndpointTest extends TestCase
{
    use ProphecyTrait;

    private ?TokenTypeHintManager $tokenTypeHintManager = null;

    private ?TokenRevocationPostEndpoint $tokenRevocationEndpoint = null;

    private ?Psr17Factory $responseFactory = null;

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

        $request = $this->buildRequest([
            'token' => 'VALID_TOKEN',
        ]);
        $request->getAttribute('client')
            ->willReturn($this->getClient())
        ;

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

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

        $request = $this->buildRequest([
            'token' => 'VALID_TOKEN',
            'token_type_hint' => 'foo',
        ]);
        $request->getAttribute('client')
            ->willReturn($this->getClient())
        ;

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

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

        $request = $this->buildRequest([
            'token' => 'VALID_TOKEN',
            'token_type_hint' => 'bar',
        ]);
        $request->getAttribute('client')
            ->willReturn($this->getClient())
        ;

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $endpoint->process($request->reveal(), $handler->reveal());

        static::assertSame(400, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertSame(
            '{"error":"unsupported_token_type","error_description":"The token type hint \"bar\" is not supported. Please use one of the following values: foo."}',
            $response->getBody()
                ->getContents()
        );
    }

    private function getTokenTypeHintManager(): TokenTypeHintManager
    {
        if ($this->tokenTypeHintManager === null) {
            $token = $this->prophesize(AccessToken::class);
            $token->getClientId()
                ->willReturn(new ClientId('CLIENT_ID'))
            ;

            $tokenType = $this->prophesize(TokenTypeHint::class);
            $tokenType->find('VALID_TOKEN')
                ->willReturn($token->reveal())
            ;
            $tokenType->find('BAD_TOKEN')
                ->willReturn(null)
            ;
            $tokenType->hint()
                ->willReturn('foo')
            ;
            $tokenType->revoke($token)
                ->will(function () {})
            ;

            $this->tokenTypeHintManager = new TokenTypeHintManager();
            $this->tokenTypeHintManager->add($tokenType->reveal());
        }

        return $this->tokenTypeHintManager;
    }

    private function getTokenRevocationPostEndpoint(): TokenRevocationPostEndpoint
    {
        if ($this->tokenRevocationEndpoint === null) {
            $this->tokenRevocationEndpoint = new TokenRevocationPostEndpoint(
                $this->getTokenTypeHintManager(),
                $this->getResponseFactory()
            );
        }

        return $this->tokenRevocationEndpoint;
    }

    private function getResponseFactory(): ResponseFactoryInterface
    {
        if ($this->responseFactory === null) {
            $this->responseFactory = new Psr17Factory();
        }

        return $this->responseFactory;
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $client = $this->prophesize(Client::class);
            $client->isPublic()
                ->willReturn(false)
            ;
            $client->getOwnerId()
                ->willReturn(new UserAccountId('USER_ACCOUNT'))
            ;
            $client->getPublicId()
                ->willReturn(new ClientId('CLIENT_ID'))
            ;
            $client->getClientId()
                ->willReturn(new ClientId('CLIENT_ID'))
            ;

            $this->client = $client->reveal();
        }

        return $this->client;
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()
            ->willReturn(http_build_query($data))
        ;
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')
            ->willReturn(true)
        ;
        $request->getHeader('Content-Type')
            ->willReturn(['application/x-www-form-urlencoded'])
        ;
        $request->getBody()
            ->willReturn($body->reveal())
        ;
        $request->getParsedBody()
            ->willReturn([])
        ;

        return $request;
    }
}
