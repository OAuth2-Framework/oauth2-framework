<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\Tests\ResponseMode;

use InvalidArgumentException;
use Nyholm\Psr7\Response;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseRenderer;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FragmentResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class ResponseModeTest extends TestCase
{
    use ProphecyTrait;

    private ?ResponseModeManager $responseModeManager = null;

    /**
     * @test
     */
    public function genericCalls(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response mode with name "foo" is not supported.');
        static::assertSame(['query', 'fragment', 'form_post'], $this->getResponseModeManager()->list());
        static::assertTrue($this->getResponseModeManager()->has('query'));
        static::assertFalse($this->getResponseModeManager()->has('foo'));
        static::assertInstanceOf(ResponseMode::class, $this->getResponseModeManager()->get('fragment'));
        $this->getResponseModeManager()
            ->get('foo')
        ;
    }

    /**
     * @test
     */
    public function buildQueryResponseForUrl(): void
    {
        $mode = $this->getResponseModeManager()
            ->get('query')
        ;
        $response = new Response();
        $response = $mode->buildResponse($response, 'https://localhost/foo?bar=bar#foo=foo', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertSame(
            ['https://localhost/foo?bar=bar&access_token=ACCESS_TOKEN#_=_'],
            $response->getHeader('Location')
        );
    }

    /**
     * @test
     */
    public function buildQueryResponseForPrivateUri(): void
    {
        $mode = $this->getResponseModeManager()
            ->get('query')
        ;
        $response = new Response();
        $response = $mode->buildResponse($response, 'com.example.app:/oauth2redirect/example-provider', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertSame(
            ['com.example.app:/oauth2redirect/example-provider?access_token=ACCESS_TOKEN#_=_'],
            $response->getHeader('Location')
        );
    }

    /**
     * @test
     */
    public function buildQueryResponseForUrn(): void
    {
        $mode = $this->getResponseModeManager()
            ->get('query')
        ;
        $response = new Response();
        $response = $mode->buildResponse($response, 'urn:ietf:wg:oauth:2.0:oob', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertSame(
            ['urn:ietf:wg:oauth:2.0:oob?access_token=ACCESS_TOKEN#_=_'],
            $response->getHeader('Location')
        );
    }

    /**
     * @test
     */
    public function buildFragmentResponseForUrl(): void
    {
        $mode = $this->getResponseModeManager()
            ->get('fragment')
        ;
        $response = new Response();
        $response = $mode->buildResponse($response, 'https://localhost/foo?bar=bar#foo=foo', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertSame(
            ['https://localhost/foo?bar=bar#access_token=ACCESS_TOKEN&_=_'],
            $response->getHeader('Location')
        );
    }

    /**
     * @test
     */
    public function buildFragmentResponseForPrivateUri(): void
    {
        $mode = $this->getResponseModeManager()
            ->get('fragment')
        ;
        $response = new Response();
        $response = $mode->buildResponse($response, 'com.example.app:/oauth2redirect/example-provider', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertSame(
            ['com.example.app:/oauth2redirect/example-provider#access_token=ACCESS_TOKEN&_=_'],
            $response->getHeader('Location')
        );
    }

    /**
     * @test
     */
    public function buildFragmentResponseForUrn(): void
    {
        $mode = $this->getResponseModeManager()
            ->get('fragment')
        ;
        $response = new Response();
        $response = $mode->buildResponse($response, 'urn:ietf:wg:oauth:2.0:oob', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertSame(
            ['urn:ietf:wg:oauth:2.0:oob#access_token=ACCESS_TOKEN&_=_'],
            $response->getHeader('Location')
        );
    }

    /**
     * @test
     */
    public function buildFormPostResponseForUrl(): void
    {
        $mode = $this->getResponseModeManager()
            ->get('form_post')
        ;
        $response = new Response();
        $response = $mode->buildResponse($response, 'https://localhost/foo?bar=bar#foo=foo', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        $response->getBody()
            ->rewind()
        ;
        $body = $response->getBody()
            ->getContents()
        ;
        static::assertSame('["https:\/\/localhost\/foo?bar=bar#_=_",{"access_token":"ACCESS_TOKEN"}]', $body);
    }

    /**
     * @test
     */
    public function buildFormPostResponseForPrivateUri(): void
    {
        $mode = $this->getResponseModeManager()
            ->get('form_post')
        ;
        $response = new Response();
        $response = $mode->buildResponse($response, 'com.example.app:/oauth2redirect/example-provider', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        $response->getBody()
            ->rewind()
        ;
        $body = $response->getBody()
            ->getContents()
        ;
        static::assertSame(
            '["com.example.app:\/oauth2redirect\/example-provider#_=_",{"access_token":"ACCESS_TOKEN"}]',
            $body
        );
    }

    /**
     * @test
     */
    public function buildFormPostResponseForUrn(): void
    {
        $mode = $this->getResponseModeManager()
            ->get('form_post')
        ;
        $response = new Response();
        $response = $mode->buildResponse($response, 'urn:ietf:wg:oauth:2.0:oob', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        $response->getBody()
            ->rewind()
        ;
        $body = $response->getBody()
            ->getContents()
        ;
        static::assertSame('["urn:ietf:wg:oauth:2.0:oob#_=_",{"access_token":"ACCESS_TOKEN"}]', $body);
    }

    private function getResponseModeManager(): ResponseModeManager
    {
        if ($this->responseModeManager === null) {
            $this->responseModeManager = new ResponseModeManager();
            $this->responseModeManager->add(new QueryResponseMode());
            $this->responseModeManager->add(new FragmentResponseMode());
            $formPostResponseRenderer = $this->prophesize(FormPostResponseRenderer::class);
            $formPostResponseRenderer->render(Argument::type('string'), [
                'access_token' => 'ACCESS_TOKEN',
            ])->will(function ($args) {
                return json_encode($args);
            });
            $this->responseModeManager->add(new FormPostResponseMode($formPostResponseRenderer->reveal()));
        }

        return $this->responseModeManager;
    }
}
