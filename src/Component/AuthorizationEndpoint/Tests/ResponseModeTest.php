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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Tests;

use Http\Message\MessageFactory\DiactorosMessageFactory;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FormPostResponseRenderer;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\FragmentResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group ResponseMode
 */
class ResponseModeTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The response mode with name "foo" is not supported.
     */
    public function genericCalls()
    {
        self::assertEquals(['query', 'fragment', 'form_post'], $this->getResponseModeManager()->list());
        self::assertTrue($this->getResponseModeManager()->has('query'));
        self::assertFalse($this->getResponseModeManager()->has('foo'));
        self::assertInstanceOf(ResponseMode::class, $this->getResponseModeManager()->get('fragment'));
        $this->getResponseModeManager()->get('foo');
    }

    /**
     * @test
     */
    public function buildQueryResponseForUrl()
    {
        $mode = $this->getResponseModeManager()->get('query');
        $response = $mode->buildResponse('https://localhost/foo?bar=bar#foo=foo', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        self::assertTrue($response->hasHeader('Location'));
        self::assertEquals(['https://localhost/foo?bar=bar&access_token=ACCESS_TOKEN#_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildQueryResponseForPrivateUri()
    {
        $mode = $this->getResponseModeManager()->get('query');
        $response = $mode->buildResponse('com.example.app:/oauth2redirect/example-provider', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        self::assertTrue($response->hasHeader('Location'));
        self::assertEquals(['com.example.app:/oauth2redirect/example-provider?access_token=ACCESS_TOKEN#_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildQueryResponseForUrn()
    {
        $mode = $this->getResponseModeManager()->get('query');
        $response = $mode->buildResponse('urn:ietf:wg:oauth:2.0:oob', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        self::assertTrue($response->hasHeader('Location'));
        self::assertEquals(['urn:ietf:wg:oauth:2.0:oob?access_token=ACCESS_TOKEN#_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildFragmentResponseForUrl()
    {
        $mode = $this->getResponseModeManager()->get('fragment');
        $response = $mode->buildResponse('https://localhost/foo?bar=bar#foo=foo', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        self::assertTrue($response->hasHeader('Location'));
        self::assertEquals(['https://localhost/foo?bar=bar#access_token=ACCESS_TOKEN&_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildFragmentResponseForPrivateUri()
    {
        $mode = $this->getResponseModeManager()->get('fragment');
        $response = $mode->buildResponse('com.example.app:/oauth2redirect/example-provider', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        self::assertTrue($response->hasHeader('Location'));
        self::assertEquals(['com.example.app:/oauth2redirect/example-provider#access_token=ACCESS_TOKEN&_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildFragmentResponseForUrn()
    {
        $mode = $this->getResponseModeManager()->get('fragment');
        $response = $mode->buildResponse('urn:ietf:wg:oauth:2.0:oob', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        self::assertTrue($response->hasHeader('Location'));
        self::assertEquals(['urn:ietf:wg:oauth:2.0:oob#access_token=ACCESS_TOKEN&_=_'], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function buildFormPostResponseForUrl()
    {
        $mode = $this->getResponseModeManager()->get('form_post');
        $response = $mode->buildResponse('https://localhost/foo?bar=bar#foo=foo', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        self::assertEquals('["https:\/\/localhost\/foo?bar=bar#_=_",{"access_token":"ACCESS_TOKEN"}]', $body);
    }

    /**
     * @test
     */
    public function buildFormPostResponseForPrivateUri()
    {
        $mode = $this->getResponseModeManager()->get('form_post');
        $response = $mode->buildResponse('com.example.app:/oauth2redirect/example-provider', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        self::assertEquals('["com.example.app:\/oauth2redirect\/example-provider#_=_",{"access_token":"ACCESS_TOKEN"}]', $body);
    }

    /**
     * @test
     */
    public function buildFormPostResponseForUrn()
    {
        $mode = $this->getResponseModeManager()->get('form_post');
        $response = $mode->buildResponse('urn:ietf:wg:oauth:2.0:oob', [
            'access_token' => 'ACCESS_TOKEN',
        ]);

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        self::assertEquals('["urn:ietf:wg:oauth:2.0:oob#_=_",{"access_token":"ACCESS_TOKEN"}]', $body);
    }

    /**
     * @var null|ResponseModeManager
     */
    private $responseModeManager = null;

    private function getResponseModeManager(): ResponseModeManager
    {
        if (null === $this->responseModeManager) {
            $this->responseModeManager = new ResponseModeManager();
            $this->responseModeManager->add(new QueryResponseMode(
                new DiactorosMessageFactory()
            ));
            $this->responseModeManager->add(new FragmentResponseMode(
                new DiactorosMessageFactory()
            ));
            $formPostResponseRenderer = $this->prophesize(FormPostResponseRenderer::class);
            $formPostResponseRenderer->render(Argument::type('string'), ['access_token' => 'ACCESS_TOKEN'])->will(function ($args) {
                return json_encode($args);
            });
            $this->responseModeManager->add(new FormPostResponseMode(
                $formPostResponseRenderer->reveal(),
                new DiactorosMessageFactory()
            ));
        }

        return $this->responseModeManager;
    }
}
