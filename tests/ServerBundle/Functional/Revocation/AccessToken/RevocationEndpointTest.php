<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\ServerBundle\Functional\Revocation\AccessToken;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationEndpoint;
use OAuth2Framework\Tests\TestBundle\Entity\AccessToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class RevocationEndpointTest extends WebTestCase
{
    protected function setUp(): void
    {
        if (! class_exists(TokenRevocationEndpoint::class)) {
            static::markTestSkipped('The component "oauth2-framework/token-revocation-endpoint" is not installed.');
        }
        parent::setUp();
    }

    /**
     * @test
     */
    public function theClientIsNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/revoke', [], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_client","error_description":"Client authentication failed."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theTokenParameterIsNotSet(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/revoke', [
            'client_id' => 'CLIENT_ID_3',
            'client_secret' => 'secret',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"The parameter \"token\" is missing."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function anUnknownTokenIsNotFound(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/revoke', [
            'client_id' => 'CLIENT_ID_3',
            'client_secret' => 'secret',
            'token' => 'FOO',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('', $response->getContent());
    }

    /**
     * @test
     */
    public function aAccessTokenIsCorrectlyRevoked(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        /** @var AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $container->get(
            \OAuth2Framework\Tests\TestBundle\Repository\AccessTokenRepository::class
        );
        $accessToken = $accessTokenRepository->create(
            new ClientId('CLIENT_ID_3'),
            new UserAccountId('john.1'),
            new DateTimeImmutable('now +1 hour'),
            new DataBag([]),
            new DataBag([]),
            null
        );
        $accessTokenId = $accessToken->getId();
        $accessTokenRepository->save($accessToken);

        $client->request('POST', '/token/revoke', [
            'client_id' => 'CLIENT_ID_3',
            'client_secret' => 'secret',
            'token' => $accessTokenId->getValue(),
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('', $response->getContent());

        $newAccessToken = $accessTokenRepository->find($accessTokenId);
        static::assertInstanceOf(AccessToken::class, $newAccessToken);
        self::AssertTrue($newAccessToken->isRevoked());
    }

    /**
     * @test
     */
    public function aAccessTokenThatOwnsToAnotherClientIsNotRevoked(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();
        /** @var AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $container->get(
            \OAuth2Framework\Tests\TestBundle\Repository\AccessTokenRepository::class
        );
        $accessToken = $accessTokenRepository->create(
            new ClientId('CLIENT_ID_2'),
            new UserAccountId('john.1'),
            new DateTimeImmutable('now +1 hour'),
            new DataBag([]),
            new DataBag([]),
            null
        );
        $accessTokenId = $accessToken->getId();
        $accessTokenRepository->save($accessToken);

        $client->request('POST', '/token/revoke', [
            'client_id' => 'CLIENT_ID_3',
            'client_secret' => 'secret',
            'token' => $accessTokenId->getValue(),
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"The parameter \"token\" is invalid."}',
            $response->getContent()
        );

        $newAccessToken = $accessTokenRepository->find($accessTokenId);
        static::assertInstanceOf(AccessToken::class, $newAccessToken);
        self::AssertFalse($newAccessToken->isRevoked());
    }
}
