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

namespace OAuth2Framework\ServerBundle\Tests\Functional\ClientRegistration;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationEndpoint;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group ClientRegistration
 */
class ClientRegistrationEndpointTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!class_exists(TokenRevocationEndpoint::class)) {
            $this->markTestSkipped('The component "oauth2-framework/client-registration-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theInitialAccessTokenExpired()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'foo.foo.foo']);
        $client->request('POST', '/client/management', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer EXPIRED_INITIAL_ACCESS_TOKEN_ID'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"Initial Access Token expired."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsMissing()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'foo.foo.foo']);
        $client->request('POST', '/client/management', [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"Initial Access Token is missing or invalid."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theInitialAccessTokenIsValidAndTheClientIsCreated()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'foo.foo.foo']);
        $client->request('POST', '/client/management', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer VALID_INITIAL_ACCESS_TOKEN_ID'], null);
        $response = $client->getResponse();
        self::assertEquals(201, $response->getStatusCode());
        self::assertEquals('application/json; charset=UTF-8', $response->headers->get('content-type'));
        $content = json_decode($response->getContent(), true);
        self::assertInternalType('array', $content);
        self::assertArrayHasKey('client_id', $content);
        /** @var ContainerInterface $container */
        $container = $client->getContainer();
        /** @var ClientRepository $clientRepository */
        $clientRepository = $container->get(\OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\ClientRepository::class);
        $client = $clientRepository->find(ClientId::create($content['client_id']));
        self::assertInstanceOf(Client::class, $client);
    }
}
