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

namespace OAuth2Framework\SecurityBundle\Tests\Functional\Security;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\SecurityBundle\Tests\TestBundle\Service\AccessTokenHandler;
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
        self::assertEquals('Bearer realm="Protected API",error="access_denied",error_description="OAuth2 authentication required. Invalid access token."', $response->headers->get('www-authenticate'));
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedButTheTokenDoesNotHaveTheRequiredScope()
    {
        $client = static::createClient();
        /** @var AccessTokenHandler $accessTokenHandler */
        $accessTokenHandler = $client->getContainer()->get(AccessTokenHandler::class);
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
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $accessTokenHandler->save($accessToken);

        $client->request('GET', '/api/hello-profile', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer ACCESS_TOKEN_WITH_INSUFFICIENT_SCOPE']);
        $response = $client->getResponse();
        self::assertEquals(403, $response->getStatusCode());
        self::assertEquals('{"scope":"profile openid","error":"access_denied","error_description":"Insufficient scope. The required scope is \"profile openid\""}', $response->getContent());
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedButTheTokenTypeIsNotAllowed()
    {
        $client = static::createClient();
        /** @var AccessTokenHandler $accessTokenHandler */
        $accessTokenHandler = $client->getContainer()->get(AccessTokenHandler::class);
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            AccessTokenId::create('ACCESS_TOKEN_WITH_BAD_TOKEN_TYPE'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_type' => 'Bearer',
                'scope' => 'openid',
            ]),
            DataBag::create([]),
            new \DateTimeImmutable('now +1 hour'),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $accessTokenHandler->save($accessToken);

        $client->request('GET', '/api/hello-token', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer ACCESS_TOKEN_WITH_BAD_TOKEN_TYPE']);
        $response = $client->getResponse();
        self::assertEquals(403, $response->getStatusCode());
        self::assertEquals('{"error":"access_denied","error_description":"Token type \"Bearer\" not allowed. Please use \"MAC\""}', $response->getContent());
    }

    /**
     * @test
     */
    public function aValidApiRequestIsReceivedAndTheAccessTokenResolverIsUsed()
    {
        $client = static::createClient();
        /** @var AccessTokenHandler $accessTokenHandler */
        $accessTokenHandler = $client->getContainer()->get(AccessTokenHandler::class);
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            AccessTokenId::create('VALID_ACCESS_TOKEN'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_type' => 'Bearer',
                'scope' => 'openid',
            ]),
            DataBag::create([]),
            new \DateTimeImmutable('now +1 hour'),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $accessTokenHandler->save($accessToken);

        $client->request('GET', '/api/hello-resolver', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer VALID_ACCESS_TOKEN']);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(json_encode($accessToken), $response->getContent());
    }
}
