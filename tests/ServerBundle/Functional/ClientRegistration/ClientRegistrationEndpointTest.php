<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\ServerBundle\Functional\ClientRegistration;

use const JSON_THROW_ON_ERROR;
use OAuth2Framework\Component\ClientRegistrationEndpoint\ClientRegistrationEndpoint;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class ClientRegistrationEndpointTest extends WebTestCase
{
    protected function setUp(): void
    {
        if (! class_exists(ClientRegistrationEndpoint::class)) {
            static::markTestSkipped('The component "oauth2-framework/client-registration-endpoint" is not installed.');
        }
        parent::setUp();
    }

    /**
     * @test
     */
    public function theInitialAccessTokenExpired(): void
    {
        $client = static::createClient();
        $client->request('POST', '/client/management', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTPS' => 'on',
            'HTTP_AUTHORIZATION' => 'Bearer EXPIRED_INITIAL_ACCESS_TOKEN_ID',
        ], '{}');
        $response = $client->getResponse();

        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"Initial Access Token expired."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsMissing(): void
    {
        $client = static::createClient();
        $client->request('POST', '/client/management', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTPS' => 'on',
        ], '{}');
        $response = $client->getResponse();

        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"Initial Access Token is missing or invalid."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsRevoked(): void
    {
        $client = static::createClient();
        $client->request('POST', '/client/management', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTPS' => 'on',
            'HTTP_AUTHORIZATION' => 'Bearer REVOKED_INITIAL_ACCESS_TOKEN_ID',
        ], '{}');
        $response = $client->getResponse();

        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"Initial Access Token is missing or invalid."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsValidAndTheClientIsCreated(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/client/management',
            json_decode(
                '{"response_types": ["code"], "redirect_uris": ["https://op.certification.openid.net:60105/authz_cb"], "contacts": ["roland@example.com"], "post_logout_redirect_uris": ["https://op.certification.openid.net:60105/logout"], "grant_types": ["authorization_code"], "application_type": "web", "request_uris": ["https://op.certification.openid.net:60105/requests/95f9263590d692e27f0a1527f44f4d7d5c1d14ef4d15c55e2c73ea3e36a3d106#Yx6JTP8P5ra40dzJ"]}',
                true,
                512,
                JSON_THROW_ON_ERROR
            ),
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTPS' => 'on',
                'HTTP_AUTHORIZATION' => 'Bearer VALID_INITIAL_ACCESS_TOKEN_ID',
            ]
        );
        $response = $client->getResponse();
        static::assertSame(201, $response->getStatusCode());
        static::assertSame('application/json; charset=UTF-8', $response->headers->get('content-type'));
        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        static::assertIsArray($content);
        static::assertArrayHasKey('client_id', $content);
        /** @var ContainerInterface $container */
        $container = $client->getContainer();
        /** @var ClientRepository $clientRepository */
        $clientRepository = $container->get(\OAuth2Framework\Tests\TestBundle\Repository\ClientRepository::class);
        $client = $clientRepository->find(new ClientId($content['client_id']));
        static::assertInstanceOf(Client::class, $client);
    }
}
