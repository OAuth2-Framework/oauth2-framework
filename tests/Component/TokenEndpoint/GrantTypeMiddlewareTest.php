<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\TokenEndpoint;

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeMiddleware;
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
final class GrantTypeMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private ?GrantTypeManager $grantTypeManager = null;

    private ?GrantTypeMiddleware $grantTypeMiddleware = null;

    /**
     * @test
     */
    public function genericCalls(): void
    {
        static::assertSame(['foo'], $this->getGrantTypeManager()->list());
        static::assertInstanceOf(GrantType::class, $this->getGrantTypeManager()->get('foo'));
    }

    /**
     * @test
     */
    public function theGrantTypeParameterIsMissing(): void
    {
        $request = $this->buildRequest([]);
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getGrantTypeMiddleware()
                ->process($request->reveal(), $handler->reveal())
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
        $request = $this->buildRequest([
            'grant_type' => 'bar',
        ]);
        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getGrantTypeMiddleware()
                ->process($request->reveal(), $handler->reveal())
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

    /**
     * @test
     */
    public function theGrantTypeIsFoundAndAssociatedToTheRequest(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->buildRequest([
            'grant_type' => 'foo',
        ]);
        $request->withAttribute('grant_type', Argument::type(GrantType::class))
            ->shouldBeCalled()
            ->willReturn($request)
        ;
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldBeCalled()
            ->willReturn($response->reveal())
        ;

        $this->getGrantTypeMiddleware()
            ->process($request->reveal(), $handler->reveal())
        ;
    }

    private function getGrantTypeManager(): GrantTypeManager
    {
        if ($this->grantTypeManager === null) {
            $this->grantTypeManager = new GrantTypeManager();
            $grantType = $this->prophesize(GrantType::class);
            $grantType->name()
                ->willReturn('foo')
            ;

            $this->grantTypeManager->add($grantType->reveal());
        }

        return $this->grantTypeManager;
    }

    private function getGrantTypeMiddleware(): GrantTypeMiddleware
    {
        if ($this->grantTypeMiddleware === null) {
            $this->grantTypeMiddleware = new GrantTypeMiddleware($this->getGrantTypeManager());
        }

        return $this->grantTypeMiddleware;
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
