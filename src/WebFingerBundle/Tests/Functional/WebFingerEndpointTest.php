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

namespace OAuth2Framework\WebFingerBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group WebFinger
 */
class WebFingerEndpointTest extends WebTestCase
{
    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithoutResourceParameter()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'my-service.com', 'HTTP_PORT' => 443]);
        $client->request('GET', '/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_request","error_description":"The parameter \"resource\" is mandatory."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAnInvalidResourceParameterBasedOnAnXRI()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'my-service.com', 'HTTP_PORT' => 443]);
        $client->request('GET', '/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => '@foo'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_request","error_description":"The resource identified with \"@foo\" does not exist or is not supported by this server."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAnInvalidResourceParameterBasedOnAnAccount()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'my-service.com', 'HTTP_PORT' => 443]);
        $client->request('GET', '/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => 'acct:john@example.com'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_request","error_description":"The resource identified with \"acct:john@example.com\" does not exist or is not supported by this server."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAnInvalidResourceParameterBasedOnAnEmail()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'my-service.com', 'HTTP_PORT' => 443]);
        $client->request('GET', '/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => 'john@example.com'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_request","error_description":"The resource identified with \"john@example.com\" does not exist or is not supported by this server."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAnInvalidResourceParameterBasedOnAnUrl()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'my-service.com', 'HTTP_PORT' => 443]);
        $client->request('GET', '/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => 'https://example.com:8080/+john'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_request","error_description":"The resource identified with \"https://example.com:8080/+john\" does not exist or is not supported by this server."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAValidResourceParameterBasedOnAnAccount()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'my-service.com', 'HTTP_PORT' => 443]);
        $client->request('GET', '/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => 'acct:john@my-service.com:443'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('{"subject":"acct:john@my-service.com:443","aliases":["https://my-service.com:443/+john"],"links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://server.example.com"}]}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAValidResourceParameterBasedOnAnUrl()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'my-service.com', 'HTTP_PORT' => 443]);
        $client->request('GET', '/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => 'https://my-service.com:443/+john'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('{"subject":"acct:john@my-service.com:443","aliases":["https://my-service.com:443/+john"],"links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://server.example.com"}]}', $response->getContent());
    }
}
