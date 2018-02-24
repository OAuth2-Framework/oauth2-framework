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

namespace OAuth2Framework\ServerBundle\Tests\Functional\Grant\ClientCredentials;

use OAuth2Framework\Component\ClientCredentialsGrant\ClientCredentialsGrantType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group ClientCredentials
 */
class ClientCredentialsGrantTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!class_exists(ClientCredentialsGrantType::class)) {
            $this->markTestSkipped('The component "oauth2-framework/client-credentials-grant" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theRequestHasNoGrantType()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'bar.bar']);
        $client->request('POST', '/token/get', [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals('{"error":"invalid_request","error_description":"The \"grant_type\" parameter is missing."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theClientIsNotAuthenticated()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'bar.bar']);
        $client->request('POST', '/token/get', ['grant_type' => 'client_credentials'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(401, $response->getStatusCode());
        self::assertEquals('Basic realm="My OAuth2 Server",charset="UTF-8",error="invalid_client",error_description="Client authentication failed."', $response->headers->get('www-authenticate'));
    }

    /**
     * @test
     */
    public function theClientIsNotKnown()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'bar.bar']);
        $client->request('POST', '/token/get', ['grant_type' => 'client_credentials', 'client_id' => 'UNKNOWN_CLIENT_ID'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(401, $response->getStatusCode());
        self::assertEquals('Basic realm="My OAuth2 Server",charset="UTF-8",error="invalid_client",error_description="Client authentication failed."', $response->headers->get('www-authenticate'));
    }

    /**
     * @test
     */
    public function theGrantTypeIsNotAllowedForTheClient()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'bar.bar']);
        $client->request('POST', '/token/get', ['grant_type' => 'client_credentials', 'client_id' => 'CLIENT_ID_1'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"unauthorized_client","error_description":"The grant type \"client_credentials\" is unauthorized for this client."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theClientIsNotConfidential()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'bar.bar']);
        $client->request('POST', '/token/get', ['grant_type' => 'client_credentials', 'client_id' => 'CLIENT_ID_2'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_client","error_description":"The client is not a confidential client."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAccessTokenIsIssued()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'bar.bar']);
        $client->request('POST', '/token/get', ['grant_type' => 'client_credentials', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertRegexp('/\{"token_type"\:"Bearer","access_token"\:"[0-9a-zA-Z-_]+","expires_in":[0-9]{4}\}/', $response->getContent());
    }
}
