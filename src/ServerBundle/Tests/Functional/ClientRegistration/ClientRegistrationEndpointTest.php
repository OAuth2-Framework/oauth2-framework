<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\Functional\ClientRegistration;

use OAuth2Framework\Component\ClientRegistrationEndpoint\ClientRegistrationEndpoint;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\ServerBundle\Tests\Functional\DatabaseTestCase;
use Psr\Container\ContainerInterface;

/**
 * @group ServerBundle
 * @group Functional
 * @group ClientRegistration
 */
class ClientRegistrationEndpointTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        if (!\class_exists(ClientRegistrationEndpoint::class)) {
            static::markTestSkipped('The component "oauth2-framework/client-registration-endpoint" is not installed.');
        }
        parent::setUp();
    }

    /**
     * @test
     */
    public function theInitialAccessTokenExpired()
    {
        $client = static::createClient();
        $client->request('POST', '/client/management', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer EXPIRED_INITIAL_ACCESS_TOKEN_ID'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_request","error_description":"Initial Access Token expired."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsMissing()
    {
        $client = static::createClient();
        $client->request('POST', '/client/management', [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_request","error_description":"Initial Access Token is missing or invalid."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsValidAndTheClientIsCreated()
    {
        $client = static::createClient();
        $client->request('POST', '/client/management', \Safe\json_decode('{"response_types": ["code"], "redirect_uris": ["https://op.certification.openid.net:60105/authz_cb"], "contacts": ["roland@example.com"], "post_logout_redirect_uris": ["https://op.certification.openid.net:60105/logout"], "grant_types": ["authorization_code"], "application_type": "web", "request_uris": ["https://op.certification.openid.net:60105/requests/95f9263590d692e27f0a1527f44f4d7d5c1d14ef4d15c55e2c73ea3e36a3d106#Yx6JTP8P5ra40dzJ"]}', true), [], ['CONTENT_TYPE' => 'application/json', 'HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer VALID_INITIAL_ACCESS_TOKEN_ID']);
        $response = $client->getResponse();
        static::assertEquals(201, $response->getStatusCode());
        static::assertEquals('application/json; charset=UTF-8', $response->headers->get('content-type'));
        $content = \Safe\json_decode($response->getContent(), true);
        static::assertIsArray($content);
        static::assertArrayHasKey('client_id', $content);
        /** @var ContainerInterface $container */
        $container = $client->getContainer();
        /** @var ClientRepository $clientRepository */
        $clientRepository = $container->get(\OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\ClientRepository::class);
        $client = $clientRepository->find(new ClientId($content['client_id']));
        static::assertInstanceOf(Client::class, $client);
    }
}
