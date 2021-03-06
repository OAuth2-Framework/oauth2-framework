<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Tests\SecurityBundle\Functional\Security;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\SecurityBundle\Functional\AccessToken;
use OAuth2Framework\SecurityBundle\Tests\TestBundle\Service\AccessTokenHandler;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group Firewall
 *
 * @internal
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
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('{"name":"World","message":"Hello World!"}', $response->getContent());
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedWithAnUnsupportedTokenType()
    {
        $client = static::createClient();
        $client->request('GET', '/api/hello/World', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'POP UNKNOWN_ACCESS_TOKEN_ID']);
        $response = $client->getResponse();
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('{"name":"World","message":"Hello World!"}', $response->getContent());
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedButTheTokenDoesNotExist()
    {
        $client = static::createClient();
        $client->request('GET', '/api/hello/World', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer UNKNOWN_ACCESS_TOKEN_ID']);
        $response = $client->getResponse();

        static::assertEquals(401, $response->getStatusCode());
        static::assertEquals('', $response->getContent());
        static::assertTrue($response->headers->has('www-authenticate'));
        static::assertEquals('Bearer realm="Protected API",error="access_denied",error_description="OAuth2 authentication required. Invalid access token."', $response->headers->get('www-authenticate'));
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedButTheTokenDoesNotHaveTheRequiredScope()
    {
        $client = static::createClient();
        /** @var AccessTokenHandler $accessTokenHandler */
        $accessTokenHandler = $client->getContainer()->get(AccessTokenHandler::class);
        $accessToken = new AccessToken(
            new AccessTokenId('ACCESS_TOKEN_WITH_INSUFFICIENT_SCOPE'),
            new ClientId('CLIENT_ID'),
            new UserAccountId('USER_ACCOUNT_ID'),
            new \DateTimeImmutable('now +1 hour'),
            new DataBag([
                'token_type' => 'Bearer',
                'scope' => 'openid',
            ]),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $accessTokenHandler->save($accessToken);

        $client->request('GET', '/api/hello-profile', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer ACCESS_TOKEN_WITH_INSUFFICIENT_SCOPE']);
        $response = $client->getResponse();
        static::assertEquals(403, $response->getStatusCode());
        static::assertEquals('{"scope":"profile openid","error":"access_denied","error_description":"Insufficient scope. The required scope is \"profile openid\""}', $response->getContent());
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedButTheTokenTypeIsNotAllowed()
    {
        $client = static::createClient();
        /** @var AccessTokenHandler $accessTokenHandler */
        $accessTokenHandler = $client->getContainer()->get(AccessTokenHandler::class);
        $accessToken = new AccessToken(
            new AccessTokenId('ACCESS_TOKEN_WITH_BAD_TOKEN_TYPE'),
            new ClientId('CLIENT_ID'),
            new UserAccountId('USER_ACCOUNT_ID'),
            new \DateTimeImmutable('now +1 hour'),
            new DataBag([
                'token_type' => 'Bearer',
                'scope' => 'openid',
            ]),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $accessTokenHandler->save($accessToken);

        $client->request('GET', '/api/hello-token', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer ACCESS_TOKEN_WITH_BAD_TOKEN_TYPE']);
        $response = $client->getResponse();
        static::assertEquals(403, $response->getStatusCode());
        static::assertEquals('{"error":"access_denied","error_description":"Token type \"Bearer\" not allowed. Please use \"MAC\""}', $response->getContent());
    }

    /**
     * @test
     */
    public function aValidApiRequestIsReceivedAndTheAccessTokenResolverIsUsed()
    {
        $client = static::createClient();
        /** @var AccessTokenHandler $accessTokenHandler */
        $accessTokenHandler = $client->getContainer()->get(AccessTokenHandler::class);
        $accessToken = new AccessToken(
            new AccessTokenId('VALID_ACCESS_TOKEN'),
            new ClientId('CLIENT_ID'),
            new UserAccountId('USER_ACCOUNT_ID'),
            new \DateTimeImmutable('now +1 hour'),
            new DataBag([
                'token_type' => 'Bearer',
                'scope' => 'openid',
            ]),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $accessTokenHandler->save($accessToken);

        $client->request('GET', '/api/hello-resolver', [], [], ['HTTPS' => 'on', 'HTTP_AUTHORIZATION' => 'Bearer VALID_ACCESS_TOKEN']);
        $response = $client->getResponse();
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals(\Safe\json_encode($accessToken), $response->getContent());
    }
}
