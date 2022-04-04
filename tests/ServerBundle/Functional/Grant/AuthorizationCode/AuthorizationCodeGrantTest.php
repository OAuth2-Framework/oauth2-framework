<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\ServerBundle\Functional\Grant\AuthorizationCode;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class AuthorizationCodeGrantTest extends WebTestCase
{
    /**
     * @test
     */
    public function theRequestHasNoGrantType(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(
            '{"error":"invalid_request","error_description":"The \"grant_type\" parameter is missing."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theClientIsNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost/callback',
            'code' => 'FOO',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(401, $response->getStatusCode());
        static::assertSame(
            'Basic realm="My OAuth2 Server",charset="UTF-8",error="invalid_client",error_description="Client authentication failed."',
            $response->headers->get('www-authenticate')
        );
    }

    /**
     * @test
     */
    public function theAuthorizationCodeParameterIsMissing(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'authorization_code',
            'client_id' => 'CLIENT_ID_1',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"Missing grant type parameter(s): code, redirect_uri."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theClientIsNotKnown(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost/callback',
            'code' => 'FOO',
            'client_id' => 'UNKNOWN_CLIENT_ID',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(401, $response->getStatusCode());
        static::assertSame(
            'Basic realm="My OAuth2 Server",charset="UTF-8",error="invalid_client",error_description="Client authentication failed."',
            $response->headers->get('www-authenticate')
        );
    }

    /**
     * @test
     */
    public function theGrantTypeIsNotAllowedForTheClient(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost/callback',
            'code' => 'FOO',
            'client_id' => 'CLIENT_ID_1',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"unauthorized_client","error_description":"The grant type \"authorization_code\" is unauthorized for this client."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theAuthorizationCodeExpired(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost/callback',
            'code' => 'EXPIRED_AUTHORIZATION_CODE',
            'client_id' => 'CLIENT_ID_3',
            'client_secret' => 'secret',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_grant","error_description":"The authorization code expired."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theAuthorizationCodeIsNotForThatClient(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost/callback',
            'code' => 'VALID_AUTHORIZATION_CODE',
            'client_id' => 'CLIENT_ID_2',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_grant","error_description":"The parameter \"code\" is invalid."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theAuthorizationCodeIsRevoked(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost/callback',
            'code' => 'REVOKED_AUTHORIZATION_CODE',
            'client_id' => 'CLIENT_ID_3',
            'client_secret' => 'secret',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_grant","error_description":"The parameter \"code\" is invalid."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theAuthorizationCodeHasAlreadyBeenUsed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost/callback',
            'code' => 'USED_AUTHORIZATION_CODE',
            'client_id' => 'CLIENT_ID_3',
            'client_secret' => 'secret',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_grant","error_description":"The parameter \"code\" is invalid."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theAccessTokenIsIssued(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost/callback',
            'code' => 'VALID_AUTHORIZATION_CODE',
            'client_id' => 'CLIENT_ID_3',
            'client_secret' => 'secret',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertMatchesRegularExpression(
            '/\{"token_type"\:"Bearer","access_token"\:"[0-9a-zA-Z-_]+","expires_in":[0-9]{4}\}/',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theAccessTokenIsIssuedForConfidentialClient(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost/callback',
            'code' => 'VALID_AUTHORIZATION_CODE_FOR_CONFIDENTIAL_CLIENT',
        ], [], [
            'HTTPS' => 'on',
            'HTTP_Authorization' => 'Basic ' . base64_encode('CLIENT_ID_5:secret'),
        ], null);
        $response = $client->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertMatchesRegularExpression(
            '/\{"token_type"\:"Bearer","access_token"\:"[0-9a-zA-Z-_]+","expires_in":[0-9]{4}\}/',
            $response->getContent()
        );
    }
}
