<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\ServerBundle\Functional\Grant\ResourceOwnerPasswordCredentialsGrant;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class ResourceOwnerPasswordCredentialsGrantTest extends WebTestCase
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
            'grant_type' => 'password',
            'username' => 'FOO',
            'password' => 'FOO',
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
    public function theParametersAreMissing(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'password',
            'client_id' => 'CLIENT_ID_1',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"Missing grant type parameter(s): username, password."}',
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
            'grant_type' => 'password',
            'username' => 'FOO',
            'password' => 'FOO',
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
            'grant_type' => 'password',
            'username' => 'FOO',
            'password' => 'FOO',
            'client_id' => 'CLIENT_ID_1',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"unauthorized_client","error_description":"The grant type \"password\" is unauthorized for this client."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theResourceOwnerPasswordCredentialsAreInvalid(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'password',
            'username' => 'FOO',
            'password' => 'FOO',
            'client_id' => 'CLIENT_ID_3',
            'client_secret' => 'secret',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_grant","error_description":"Invalid username and password combination."}',
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
            'grant_type' => 'password',
            'username' => 'john.1',
            'password' => 'password.1',
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
