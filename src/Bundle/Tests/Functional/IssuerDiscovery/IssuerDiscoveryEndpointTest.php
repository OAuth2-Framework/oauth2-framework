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

namespace OAuth2Framework\Bundle\Tests\Functional\IssuerDiscovery;

use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IssuerDiscoveryEndpoint;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group Bundle
 * @group Functional
 * @group Grant
 * @group IssuerDiscovery
 */
class IssuerDiscoveryEndpointTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!class_exists(IssuerDiscoveryEndpoint::class)) {
            $this->markTestSkipped('The component "oauth2-framework/issuer-discovery-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function aClientSendAnIssuerDiscoveryRequestWithoutRelParameter()
    {
        $client = static::createClient();
        $client->request('GET', 'https://oauth2.test/.well-known/webfinger', [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"The parameter \"rel\" is mandatory."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidRelParameter()
    {
        $client = static::createClient();
        $client->request('GET', 'https://oauth2.test/.well-known/webfinger', ['rel' => 'foo.bar'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"Unsupported \"rel\" parameter value."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnIssuerDiscoveryRequestWithoutResourceParameter()
    {
        $client = static::createClient();
        $client->request('GET', 'https://oauth2.test/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"The parameter \"resource\" is mandatory."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidResourceParameterBasedOnAnXRI()
    {
        $client = static::createClient();
        $client->request('GET', 'https://oauth2.test/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => '@foo'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"Unsupported Extensible Resource Identifier (XRI) resource value."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidResourceParameterBasedOnAnAccount()
    {
        $client = static::createClient();
        $client->request('GET', 'https://oauth2.test/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => 'acct:john@example.com'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"Unsupported domain."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidResourceParameterBasedOnAnEmail()
    {
        $client = static::createClient();
        $client->request('GET', 'https://oauth2.test/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => 'john@example.com'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"Unsupported domain."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAnInvalidResourceParameterBasedOnAnUrl()
    {
        $client = static::createClient();
        $client->request('GET', 'https://oauth2.test/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => 'https://example.com:8080/+john'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"Unsupported domain."}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAValidResourceParameterBasedOnAnAccount()
    {
        $client = static::createClient();
        $client->request('GET', 'https://oauth2.test/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => 'acct:john@my-service.com:9000'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('{"subject":"acct:john@my-service.com:9000","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://server.example.com"}]}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAValidResourceParameterBasedOnAnEmail()
    {
        $client = static::createClient();
        $client->request('GET', 'https://oauth2.test/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => 'john@my-service.com:9000'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('{"subject":"john@my-service.com:9000","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://server.example.com"}]}', $response->getContent());
    }

    /**
     * @test
     */
    public function aClientSendAnIssuerDiscoveryRequestWithAValidResourceParameterBasedOnAnUrl()
    {
        $client = static::createClient();
        $client->request('GET', 'https://oauth2.test/.well-known/webfinger', ['rel' => 'http://openid.net/specs/connect/1.0/issuer', 'resource' => 'https://my-service.com:9000/+john'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('{"subject":"https://my-service.com:9000/+john","links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://server.example.com"}]}', $response->getContent());
    }
}
