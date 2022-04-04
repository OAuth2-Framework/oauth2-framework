<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\ServerBundle\Functional\Grant\RefreshToken;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class RefreshTokenGrantTest extends WebTestCase
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
            'grant_type' => 'refresh_token',
            'refresh_token' => 'FOO',
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
    public function theRefreshTokenParameterIsMissing(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'refresh_token',
            'client_id' => 'CLIENT_ID_1',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"Missing grant type parameter(s): refresh_token."}',
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
            'grant_type' => 'refresh_token',
            'refresh_token' => 'FOO',
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
            'grant_type' => 'refresh_token',
            'refresh_token' => 'FOO',
            'client_id' => 'CLIENT_ID_1',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"unauthorized_client","error_description":"The grant type \"refresh_token\" is unauthorized for this client."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theRefreshTokenExpired(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'refresh_token',
            'refresh_token' => 'EXPIRED_REFRESH_TOKEN',
            'client_id' => 'CLIENT_ID_3',
            'client_secret' => 'secret',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_grant","error_description":"The refresh token expired."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theRefreshTokenIsNotForThatClient(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'refresh_token',
            'refresh_token' => 'VALID_REFRESH_TOKEN',
            'client_id' => 'CLIENT_ID_2',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_grant","error_description":"The parameter \"refresh_token\" is invalid."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theRefreshTokenIsRevoked(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'refresh_token',
            'refresh_token' => 'REVOKED_REFRESH_TOKEN',
            'client_id' => 'CLIENT_ID_3',
            'client_secret' => 'secret',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_grant","error_description":"The parameter \"refresh_token\" is invalid."}',
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
            'grant_type' => 'refresh_token',
            'refresh_token' => 'VALID_REFRESH_TOKEN',
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
}
