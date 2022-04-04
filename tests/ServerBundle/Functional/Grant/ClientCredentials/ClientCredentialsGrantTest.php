<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\ServerBundle\Functional\Grant\ClientCredentials;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class ClientCredentialsGrantTest extends WebTestCase
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
            'grant_type' => 'client_credentials',
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
    public function theClientIsNotKnown(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'client_credentials',
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
            'grant_type' => 'client_credentials',
            'client_id' => 'CLIENT_ID_1',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"unauthorized_client","error_description":"The grant type \"client_credentials\" is unauthorized for this client."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theClientIsNotConfidential(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'client_credentials',
            'client_id' => 'CLIENT_ID_2',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_client","error_description":"The client is not a confidential client."}',
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
            'grant_type' => 'client_credentials',
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
