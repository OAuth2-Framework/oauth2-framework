<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\TokenType;

use InvalidArgumentException;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Core\TokenType\TokenTypeMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
final class TokenTypeMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private ?TokenTypeMiddleware $tokenTypeMiddleware = null;

    private ?TokenTypeManager $tokenTypeManager = null;

    /**
     * @test
     */
    public function noTokenTypeFoundInTheRequest(): void
    {
        $request = $this->buildRequest([]);
        $request->withAttribute('token_type', Argument::type(TokenType::class))->willReturn($request)->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn($response->reveal());

        $this->getTokenTypeMiddleware()
            ->process($request->reveal(), $handler->reveal())
        ;
    }

    /**
     * @test
     */
    public function aTokenTypeIsFoundInTheRequest(): void
    {
        $request = $this->buildRequest([
            'token_type' => 'foo',
        ]);
        $request->withAttribute('token_type', Argument::type(TokenType::class))->willReturn($request)->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn($response->reveal());

        $this->getTokenTypeMiddleware()
            ->process($request->reveal(), $handler->reveal())
        ;
    }

    /**
     * @test
     */
    public function aTokenTypeIsFoundInTheRequestButNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported token type "bar".');
        $request = $this->buildRequest([
            'token_type' => 'bar',
        ]);

        $response = $this->prophesize(ResponseInterface::class);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))->willReturn($response->reveal());

        $this->getTokenTypeMiddleware()
            ->process($request->reveal(), $handler->reveal())
        ;
    }

    private function getTokenTypeMiddleware(): TokenTypeMiddleware
    {
        if ($this->tokenTypeMiddleware === null) {
            $this->tokenTypeMiddleware = new TokenTypeMiddleware($this->getTokenTypeManager(), true);
        }

        return $this->tokenTypeMiddleware;
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
