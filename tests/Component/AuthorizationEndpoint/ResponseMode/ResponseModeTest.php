<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\Tests\ResponseMode;

use InvalidArgumentException;
use Nyholm\Psr7\Response;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class ResponseModeTest extends OAuth2TestCase
{
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
        $response = $mode->buildResponse('https://localhost/foo?bar=bar#foo=foo', [
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
        $response = $mode->buildResponse('com.example.app:/oauth2redirect/example-provider', [
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
        $response = $mode->buildResponse('urn:ietf:wg:oauth:2.0:oob', [
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
        $response = $mode->buildResponse('https://localhost/foo?bar=bar#foo=foo', [
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
        $response = $mode->buildResponse('com.example.app:/oauth2redirect/example-provider', [
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
        $response = $mode->buildResponse('urn:ietf:wg:oauth:2.0:oob', [
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
        $response = $mode->buildResponse('https://localhost/foo?bar=bar#foo=foo', [
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
        $response = $mode->buildResponse('com.example.app:/oauth2redirect/example-provider', [
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
        $response = $mode->buildResponse('urn:ietf:wg:oauth:2.0:oob', [
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
}
