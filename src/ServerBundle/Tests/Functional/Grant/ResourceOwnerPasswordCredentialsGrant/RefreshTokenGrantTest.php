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

namespace OAuth2Framework\ServerBundle\Tests\Functional\Grant\ResourceOwnerPasswordCredentials;

use OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialsGrantType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group ResourceOwnerPasswordCredentials
 */
class ResourceOwnerPasswordCredentialsGrantTest extends WebTestCase
{
    protected function setUp()
    {
        if (!\class_exists(ResourceOwnerPasswordCredentialsGrantType::class)) {
            static::markTestSkipped('The component "oauth2-framework/resource-owner-password-credentials-grant" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theRequestHasNoGrantType()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals('{"error":"invalid_request","error_description":"The \"grant_type\" parameter is missing."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theClientIsNotAuthenticated()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'password', 'username' => 'FOO', 'password' => 'FOO'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(401, $response->getStatusCode());
        static::assertEquals('Basic realm="My OAuth2 Server",charset="UTF-8",error="invalid_client",error_description="Client authentication failed."', $response->headers->get('www-authenticate'));
    }

    /**
     * @test
     */
    public function theParametersAreMissing()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'password', 'client_id' => 'CLIENT_ID_1'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_request","error_description":"Missing grant type parameter(s): username, password."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theClientIsNotKnown()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'password', 'username' => 'FOO', 'password' => 'FOO', 'client_id' => 'UNKNOWN_CLIENT_ID'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(401, $response->getStatusCode());
        static::assertEquals('Basic realm="My OAuth2 Server",charset="UTF-8",error="invalid_client",error_description="Client authentication failed."', $response->headers->get('www-authenticate'));
    }

    /**
     * @test
     */
    public function theGrantTypeIsNotAllowedForTheClient()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'password', 'username' => 'FOO', 'password' => 'FOO', 'client_id' => 'CLIENT_ID_1'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"unauthorized_client","error_description":"The grant type \"password\" is unauthorized for this client."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theResourceOwnerPasswordCredentialsAreInvalid()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'password', 'username' => 'FOO', 'password' => 'FOO', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_grant","error_description":"Invalid username and password combination."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAccessTokenIsIssued()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'password', 'username' => 'john.1', 'password' => 'password.1', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(200, $response->getStatusCode());
        self::assertRegexp('/\{"token_type"\:"Bearer","access_token"\:"[0-9a-zA-Z-_]+","expires_in":[0-9]{4}\}/', $response->getContent());
    }
}
