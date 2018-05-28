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

namespace OAuth2Framework\ServerBundle\Tests\Functional\Grant\AuthorizationCode;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeGrantType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group AuthorizationCode
 */
class AuthorizationCodeGrantTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!class_exists(AuthorizationCodeGrantType::class)) {
            $this->markTestSkipped('The component "oauth2-framework/authorization-code-grant" is not installed.');
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
        self::assertEquals('{"error":"invalid_request","error_description":"The \"grant_type\" parameter is missing."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theClientIsNotAuthenticated()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'FOO'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(401, $response->getStatusCode());
        self::assertEquals('Basic realm="My OAuth2 Server",charset="UTF-8",error="invalid_client",error_description="Client authentication failed."', $response->headers->get('www-authenticate'));
    }

    /**
     * @test
     */
    public function theAuthorizationCodeParameterIsMissing()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'client_id' => 'CLIENT_ID_1'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"Missing grant type parameter(s): code, redirect_uri."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theClientIsNotKnown()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'FOO', 'client_id' => 'UNKNOWN_CLIENT_ID'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(401, $response->getStatusCode());
        self::assertEquals('Basic realm="My OAuth2 Server",charset="UTF-8",error="invalid_client",error_description="Client authentication failed."', $response->headers->get('www-authenticate'));
    }

    /**
     * @test
     */
    public function theGrantTypeIsNotAllowedForTheClient()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'FOO', 'client_id' => 'CLIENT_ID_1'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"unauthorized_client","error_description":"The grant type \"authorization_code\" is unauthorized for this client."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAuthorizationCodeExpired()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'EXPIRED_AUTHORIZATION_CODE', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_grant","error_description":"The authorization code expired."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAuthorizationCodeIsNotForThatClient()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'VALID_AUTHORIZATION_CODE', 'client_id' => 'CLIENT_ID_2'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_grant","error_description":"The parameter \"code\" is invalid."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAuthorizationCodeIsRevoked()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'REVOKED_AUTHORIZATION_CODE', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_grant","error_description":"The parameter \"code\" is invalid."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAccessTokenIsIssued()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'VALID_AUTHORIZATION_CODE', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        dump($response->getContent());
        self::assertEquals(200, $response->getStatusCode());
        self::assertRegexp('/\{"token_type"\:"Bearer","access_token"\:"[0-9a-zA-Z-_]+","expires_in":[0-9]{4}\}/', $response->getContent());
    }

    /**
     * @test
     */
    public function theAccessTokenIsIssuedForConfidentialClient()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'VALID_AUTHORIZATION_CODE_FOR_CONFIDENTIAL_CLIENT'], [], ['HTTPS' => 'on', 'HTTP_Authorization' => 'Basic '.base64_encode('CLIENT_ID_5:secret')], null);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertRegexp('/\{"token_type"\:"Bearer","access_token"\:"[0-9a-zA-Z-_]+","expires_in":[0-9]{4}\}/', $response->getContent());
    }
}
