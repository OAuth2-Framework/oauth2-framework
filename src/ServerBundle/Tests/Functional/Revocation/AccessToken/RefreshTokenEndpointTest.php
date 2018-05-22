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

namespace OAuth2Framework\ServerBundle\Tests\Functional\Revocation\AccessToken;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenIdGenerator;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationEndpoint;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group Revocation
 */
class RevocationEndpointTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!class_exists(TokenRevocationEndpoint::class)) {
            $this->markTestSkipped('The component "oauth2-framework/token-revocation-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theClientIsNotAuthenticated()
    {
        $client = static::createClient();
        $client->request('POST', '/token/revoke', [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_client","error_description":"Client authentication failed."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theTokenParameterIsNotSet()
    {
        $client = static::createClient();
        $client->request('POST', '/token/revoke', ['client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"The parameter \"token\" is missing."}', $response->getContent());
    }

    /**
     * @test
     */
    public function anUnknownTokenIsNotFound()
    {
        $client = static::createClient();
        $client->request('POST', '/token/revoke', ['client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret', 'token' => 'FOO'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('', $response->getContent());
    }

    /**
     * @test
     */
    public function aAccessTokenIsCorrectlyRevoked()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        /** @var AccessTokenIdGenerator $accessTokenIdGenerator */
        $accessTokenIdGenerator = $container->get(\OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AccessTokenIdGenerator::class);
        $accessTokenId = $accessTokenIdGenerator->createAccessTokenId(
            UserAccountId::create('john.1'),
            ClientId::create('CLIENT_ID_3'),
            DataBag::create([]),
            DataBag::create([]),
            null
        );

        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            $accessTokenId,
            UserAccountId::create('john.1'),
            ClientId::create('CLIENT_ID_3'),
            DataBag::create([]),
            DataBag::create([]),
            new \DateTimeImmutable('now +1 hour'),
            null
        );

        /** @var AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $container->get(\OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AccessTokenRepository::class);
        $accessTokenRepository->save($accessToken);

        $client->request('POST', '/token/revoke', ['client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret', 'token' => $accessToken->getTokenId()->getValue()], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('', $response->getContent());

        $newAccessToken = $accessTokenRepository->find($accessToken->getTokenId());
        self::assertInstanceOf(AccessToken::class, $newAccessToken);
        self::AssertTrue($newAccessToken->isRevoked());
    }

    /**
     * @test
     */
    public function aAccessTokenThatOwnsToAnotherClientIsNotRevoked()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        /** @var AccessTokenIdGenerator $accessTokenIdGenerator */
        $accessTokenIdGenerator = $container->get(\OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AccessTokenIdGenerator::class);
        $accessTokenId = $accessTokenIdGenerator->createAccessTokenId(
            UserAccountId::create('john.1'),
            ClientId::create('CLIENT_ID_2'),
            DataBag::create([]),
            DataBag::create([]),
            null
        );

        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            $accessTokenId,
            UserAccountId::create('john.1'),
            ClientId::create('CLIENT_ID_2'),
            DataBag::create([]),
            DataBag::create([]),
            new \DateTimeImmutable('now +1 hour'),
            null
        );

        $client = static::createClient();
        $container = $client->getContainer();
        /** @var AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $container->get(\OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AccessTokenRepository::class);
        $accessTokenRepository->save($accessToken);

        $client->request('POST', '/token/revoke', ['client_id' => 'CLIENT_ID_3', 'client_secret' => 'secret', 'token' => $accessToken->getTokenId()->getValue()], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_request","error_description":"The parameter \"token\" is invalid."}', $response->getContent());

        $newAccessToken = $accessTokenRepository->find($accessToken->getTokenId());
        self::assertInstanceOf(AccessToken::class, $newAccessToken);
        self::AssertFalse($newAccessToken->isRevoked());
    }
}
