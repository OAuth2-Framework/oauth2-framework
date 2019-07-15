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

namespace OAuth2Framework\ServerBundle\Tests\Functional\Grant\RefreshToken;

use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenGrantType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group RefreshToken
 *
 * @internal
 * @coversNothing
 */
class RefreshTokenGrantTest extends WebTestCase
{
    protected function setUp(): void
    {
        if (!class_exists(RefreshTokenGrantType::class)) {
            static::markTestSkipped('The component "oauth2-framework/refresh-token-grant" is not installed.');
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
        $client->request('POST', '/token/get', ['grant_type' => 'refresh_token', 'refresh_token' => 'FOO'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(401, $response->getStatusCode());
        static::assertEquals('Basic realm="My OAuth2 Server",charset="UTF-8",error="invalid_client",error_description="Client authentication failed."', $response->headers->get('www-authenticate'));
    }

    /**
     * @test
     */
    public function theRefreshTokenParameterIsMissing()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'refresh_token', 'client_id' => 'CLIENT_ID_1'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_request","error_description":"Missing grant type parameter(s): refresh_token."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theClientIsNotKnown()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'refresh_token', 'refresh_token' => 'FOO', 'client_id' => 'UNKNOWN_CLIENT_ID'], [], ['HTTPS' => 'on'], null);
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
        $client->request('POST', '/token/get', ['grant_type' => 'refresh_token', 'refresh_token' => 'FOO', 'client_id' => 'CLIENT_ID_1'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"unauthorized_client","error_description":"The grant type \"refresh_token\" is unauthorized for this client."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theRefreshTokenExpired()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'refresh_token', 'refresh_token' => 'EXPIRED_REFRESH_TOKEN', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_grant","error_description":"The refresh token expired."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theRefreshTokenIsNotForThatClient()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'refresh_token', 'refresh_token' => 'VALID_REFRESH_TOKEN', 'client_id' => 'CLIENT_ID_2'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_grant","error_description":"The parameter \"refresh_token\" is invalid."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theRefreshTokenIsRevoked()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'refresh_token', 'refresh_token' => 'REVOKED_REFRESH_TOKEN', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_grant","error_description":"The parameter \"refresh_token\" is invalid."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theAccessTokenIsIssued()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'refresh_token', 'refresh_token' => 'VALID_REFRESH_TOKEN', 'client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(200, $response->getStatusCode());
        self::assertRegexp('/\{"token_type"\:"Bearer","access_token"\:"[0-9a-zA-Z-_]+","expires_in":[0-9]{4}\}/', $response->getContent());
    }
}
