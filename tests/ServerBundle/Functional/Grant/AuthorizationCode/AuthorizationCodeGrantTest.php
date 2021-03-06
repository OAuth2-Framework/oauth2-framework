<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
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
 *
 * @internal
 */
class AuthorizationCodeGrantTest extends WebTestCase
{
    protected function setUp(): void
    {
        if (!class_exists(AuthorizationCodeGrantType::class)) {
            static::markTestSkipped('The component "oauth2-framework/authorization-code-grant" is not installed.');
        }
        parent::setUp();
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
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'FOO'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(401, $response->getStatusCode());
        static::assertEquals('Basic realm="My OAuth2 Server",charset="UTF-8",error="invalid_client",error_description="Client authentication failed."', $response->headers->get('www-authenticate'));
    }

    /**
     * @test
     */
    public function theAuthorizationCodeParameterIsMissing()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'client_id' => 'CLIENT_ID_1'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_request","error_description":"Missing grant type parameter(s): code, redirect_uri."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theClientIsNotKnown()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'FOO', 'client_id' => 'UNKNOWN_CLIENT_ID'], [], ['HTTPS' => 'on'], null);
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
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'FOO', 'client_id' => 'CLIENT_ID_1'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"unauthorized_client","error_description":"The grant type \"authorization_code\" is unauthorized for this client."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAuthorizationCodeExpired()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'EXPIRED_AUTHORIZATION_CODE', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_grant","error_description":"The authorization code expired."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAuthorizationCodeIsNotForThatClient()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'VALID_AUTHORIZATION_CODE', 'client_id' => 'CLIENT_ID_2'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_grant","error_description":"The parameter \"code\" is invalid."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAuthorizationCodeIsRevoked()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'REVOKED_AUTHORIZATION_CODE', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_grant","error_description":"The parameter \"code\" is invalid."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAuthorizationCodeHasAlreadyBeenUsed()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'USED_AUTHORIZATION_CODE', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_grant","error_description":"The parameter \"code\" is invalid."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAccessTokenIsIssued()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'VALID_AUTHORIZATION_CODE', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(200, $response->getStatusCode());
        self::assertMatchesRegularExpression('/\{"token_type"\:"Bearer","access_token"\:"[0-9a-zA-Z-_]+","expires_in":[0-9]{4}\}/', $response->getContent());
    }

    /**
     * @test
     */
    public function theAccessTokenIsIssuedForConfidentialClient()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost/callback', 'code' => 'VALID_AUTHORIZATION_CODE_FOR_CONFIDENTIAL_CLIENT'], [], ['HTTPS' => 'on', 'HTTP_Authorization' => 'Basic '.base64_encode('CLIENT_ID_5:secret')], null);
        $response = $client->getResponse();
        static::assertEquals(200, $response->getStatusCode());
        self::assertMatchesRegularExpression('/\{"token_type"\:"Bearer","access_token"\:"[0-9a-zA-Z-_]+","expires_in":[0-9]{4}\}/', $response->getContent());
    }
}
