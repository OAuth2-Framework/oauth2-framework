<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\SecurityBundle\Functional\Security;

use DateTimeImmutable;
use const JSON_THROW_ON_ERROR;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\TestBundle\Entity\AccessToken;
use OAuth2Framework\Tests\TestBundle\Repository\AccessTokenRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class SecurityBundleTest extends WebTestCase
{
    /**
     * @test
     */
    public function anApiRequestWithoutAccessTokenIsReceived(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/hello/World');
        $response = $client->getResponse();
        static::assertSame('{"name":"World","message":"Hello World!"}', $response->getContent());
        static::assertSame(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedWithAnUnsupportedTokenType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/hello/World', [], [], [
            'HTTPS' => 'on',
            'HTTP_AUTHORIZATION' => 'POP UNKNOWN_ACCESS_TOKEN_ID',
        ]);
        $response = $client->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('{"name":"World","message":"Hello World!"}', $response->getContent());
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedButTheTokenDoesNotExist(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/hello/World', [], [], [
            'HTTPS' => 'on',
            'HTTP_AUTHORIZATION' => 'Bearer UNKNOWN_ACCESS_TOKEN_ID',
        ]);
        $response = $client->getResponse();

        static::assertSame(401, $response->getStatusCode());
        static::assertSame('', $response->getContent());
        static::assertTrue($response->headers->has('www-authenticate'));
        static::assertSame(
            'Bearer realm="Protected API",error="access_denied",error_description="OAuth2 authentication required. Missing or invalid access token."',
            $response->headers->get('www-authenticate')
        );
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedButTheTokenDoesNotHaveTheRequiredScope(): void
    {
        $client = static::createClient();
        /** @var AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $client->getContainer()
            ->get(AccessTokenRepository::class)
        ;
        $accessToken = new AccessToken(
            AccessTokenId::create('ACCESS_TOKEN_WITH_INSUFFICIENT_SCOPE'),
            new ClientId('CLIENT_ID'),
            new UserAccountId('USER_ACCOUNT_ID'),
            new DateTimeImmutable('now +1 hour'),
            new DataBag([
                'token_type' => 'Bearer',
                'scope' => 'openid',
            ]),
            new DataBag([]),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $accessTokenRepository->save($accessToken);

        $client->request('GET', '/api/hello-profile', [], [], [
            'HTTPS' => 'on',
            'HTTP_AUTHORIZATION' => 'Bearer ACCESS_TOKEN_WITH_INSUFFICIENT_SCOPE',
        ]);
        $response = $client->getResponse();
        static::assertSame(403, $response->getStatusCode());
        static::assertSame(
            '{"scope":"profile openid","error":"access_denied","error_description":"Insufficient scope. The required scope is \"profile openid\""}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function anApiRequestIsReceivedButTheTokenTypeIsNotAllowed(): void
    {
        $client = static::createClient();
        /** @var AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $client->getContainer()
            ->get(AccessTokenRepository::class)
        ;
        $accessToken = new AccessToken(
            AccessTokenId::create('ACCESS_TOKEN_WITH_BAD_TOKEN_TYPE'),
            new ClientId('CLIENT_ID'),
            new UserAccountId('USER_ACCOUNT_ID'),
            new DateTimeImmutable('now +1 hour'),
            new DataBag([
                'token_type' => 'Bearer',
                'scope' => 'openid',
            ]),
            new DataBag([]),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $accessTokenRepository->save($accessToken);

        $client->request('GET', '/api/hello-token', [], [], [
            'HTTPS' => 'on',
            'HTTP_AUTHORIZATION' => 'Bearer ACCESS_TOKEN_WITH_BAD_TOKEN_TYPE',
        ]);
        $response = $client->getResponse();
        static::assertSame(403, $response->getStatusCode());
        static::assertSame(
            '{"error":"access_denied","error_description":"Token type \"Bearer\" not allowed. Please use \"MAC\""}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function aValidApiRequestIsReceivedAndTheAccessTokenResolverIsUsed(): void
    {
        $client = static::createClient();
        /** @var AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $client->getContainer()
            ->get(AccessTokenRepository::class)
        ;
        $accessToken = new AccessToken(
            AccessTokenId::create('VALID_ACCESS_TOKEN'),
            new ClientId('CLIENT_ID'),
            new UserAccountId('USER_ACCOUNT_ID'),
            new DateTimeImmutable('now +1 hour'),
            new DataBag([
                'token_type' => 'Bearer',
                'scope' => 'openid',
            ]),
            new DataBag([]),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $accessTokenRepository->save($accessToken);

        $client->request('GET', '/api/hello-resolver', [], [], [
            'HTTPS' => 'on',
            'HTTP_AUTHORIZATION' => 'Bearer VALID_ACCESS_TOKEN',
        ]);
        $response = $client->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertSame(json_encode($accessToken, JSON_THROW_ON_ERROR), $response->getContent());
    }
}
