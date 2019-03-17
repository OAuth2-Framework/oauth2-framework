<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\Tests\ResponseMode;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseRenderer;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FragmentResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zend\Diactoros\Response;

/**
 * @group ResponseMode
 */
final class ResponseModeTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The response mode with name "foo" is not supported.
     */
    public function genericCalls()
    {
        static::assertEquals(['query', 'fragment', 'form_post'], $this->getResponseModeManager()->list());
        static::assertTrue($this->getResponseModeManager()->has('query'));
        static::assertFalse($this->getResponseModeManager()->has('foo'));
        static::assertInstanceOf(ResponseMode::class, $this->getResponseModeManager()->get('fragment'));
        $this->getResponseModeManager()->get('foo');
    }

    /**
     * @test
     */
    public function buildQueryResponseForUrl()
    {
        $mode = $this->getResponseModeManager()->get('query');
        $response = new Response();
        $response = $mode->buildResponse($response, 'https://localhost/foo?bar=bar#foo=foo', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertEquals(['https://localhost/foo?bar=bar&access_token=ACCESS_TOKEN#_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildQueryResponseForPrivateUri()
    {
        $mode = $this->getResponseModeManager()->get('query');
        $response = new Response();
        $response = $mode->buildResponse($response, 'com.example.app:/oauth2redirect/example-provider', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertEquals(['com.example.app:/oauth2redirect/example-provider?access_token=ACCESS_TOKEN#_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildQueryResponseForUrn()
    {
        $mode = $this->getResponseModeManager()->get('query');
        $response = new Response();
        $response = $mode->buildResponse($response, 'urn:ietf:wg:oauth:2.0:oob', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertEquals(['urn:ietf:wg:oauth:2.0:oob?access_token=ACCESS_TOKEN#_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildFragmentResponseForUrl()
    {
        $mode = $this->getResponseModeManager()->get('fragment');
        $response = new Response();
        $response = $mode->buildResponse($response, 'https://localhost/foo?bar=bar#foo=foo', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertEquals(['https://localhost/foo?bar=bar#access_token=ACCESS_TOKEN&_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildFragmentResponseForPrivateUri()
    {
        $mode = $this->getResponseModeManager()->get('fragment');
        $response = new Response();
        $response = $mode->buildResponse($response, 'com.example.app:/oauth2redirect/example-provider', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertEquals(['com.example.app:/oauth2redirect/example-provider#access_token=ACCESS_TOKEN&_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildFragmentResponseForUrn()
    {
        $mode = $this->getResponseModeManager()->get('fragment');
        $response = new Response();
        $response = $mode->buildResponse($response, 'urn:ietf:wg:oauth:2.0:oob', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        static::assertTrue($response->hasHeader('Location'));
        static::assertEquals(['urn:ietf:wg:oauth:2.0:oob#access_token=ACCESS_TOKEN&_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildFormPostResponseForUrl()
    {
        $mode = $this->getResponseModeManager()->get('form_post');
        $response = new Response();
        $response = $mode->buildResponse($response, 'https://localhost/foo?bar=bar#foo=foo', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        static::assertEquals('["https:\/\/localhost\/foo?bar=bar#_=_",{"access_token":"ACCESS_TOKEN"}]', $body);
    }

    /**
     * @test
     */
    public function buildFormPostResponseForPrivateUri()
    {
        $mode = $this->getResponseModeManager()->get('form_post');
        $response = new Response();
        $response = $mode->buildResponse($response, 'com.example.app:/oauth2redirect/example-provider', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        static::assertEquals('["com.example.app:\/oauth2redirect\/example-provider#_=_",{"access_token":"ACCESS_TOKEN"}]', $body);
    }

    /**
     * @test
     */
    public function buildFormPostResponseForUrn()
    {
        $mode = $this->getResponseModeManager()->get('form_post');
        $response = new Response();
        $response = $mode->buildResponse($response, 'urn:ietf:wg:oauth:2.0:oob', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        static::assertEquals('["urn:ietf:wg:oauth:2.0:oob#_=_",{"access_token":"ACCESS_TOKEN"}]', $body);
    }

    /**
     * @var ResponseModeManager|null
     */
    private $responseModeManager;

    private function getResponseModeManager(): ResponseModeManager
    {
        if (null === $this->responseModeManager) {
            $this->responseModeManager = new ResponseModeManager();
            $this->responseModeManager->add(new QueryResponseMode(
            ));
            $this->responseModeManager->add(new FragmentResponseMode(
            ));
            $formPostResponseRenderer = $this->prophesize(FormPostResponseRenderer::class);
            $formPostResponseRenderer->render(Argument::type('string'), ['access_token' => 'ACCESS_TOKEN'])->will(function ($args) {
                return \Safe\json_encode($args);
            });
            $this->responseModeManager->add(new FormPostResponseMode(
                $formPostResponseRenderer->reveal()
            ));
        }

        return $this->responseModeManager;
    }
}
