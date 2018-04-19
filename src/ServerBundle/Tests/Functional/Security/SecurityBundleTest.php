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

namespace OAuth2Framework\ServerBundle\Tests\Functional;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\AccessTokenRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group Firewall
 */
class SecurityBundleTest extends WebTestCase
{
    /**
     * @test
     */
    public function anApiRequestWithoutAccessTokenIsReceived()
    {
        $client = static::createClient();
        $client->request('GET', '/api/hello/World');
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('{"name":"World","message":"Hello World!"}', $response->getContent());
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedWithAnUnsupportedTokenType()
    {
        $client = static::createClient();
        $client->request('GET', '/api/hello/World', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'POP UNKNOWN_ACCESS_TOKEN_ID']);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('{"name":"World","message":"Hello World!"}', $response->getContent());
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedButTheTokenDoesNotExist()
    {
        $client = static::createClient();
        $client->request('GET', '/api/hello/World', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer UNKNOWN_ACCESS_TOKEN_ID']);
        $response = $client->getResponse();
        self::assertEquals(401, $response->getStatusCode());
        self::assertEquals('', $response->getContent());
        self::assertTrue($response->headers->has('www-authenticate'));
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedButTheTokenDoesNotHaveTheRequiredScope()
    {
        $client = static::createClient();
        /** @var AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $client->getContainer()->get(AccessTokenRepository::class);
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            AccessTokenId::create('ACCESS_TOKEN_WITH_INSUFFICIENT_SCOPE'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_type' => 'Bearer',
                'scope' => 'openid',
            ]),
            DataBag::create([]),
            new \DateTimeImmutable('now +1 hour'),
            ResourceServerId::create('RESOURCE_SERVER_iD')
        );
        $accessTokenRepository->save($accessToken);

        $client->request('GET', '/api/hello-profile', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer ACCESS_TOKEN_WITH_INSUFFICIENT_SCOPE']);
        $response = $client->getResponse();
        self::assertEquals(403, $response->getStatusCode());
        self::assertEquals('{"scope":"profile openid","error":"access_denied","error_description":"Insufficient scope. The required scope is \"profile openid\""}', $response->getContent());
    }
}
