<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\BearerTokenType;

use DateTimeImmutable;
use OAuth2Framework\Component\BearerTokenType\AuthorizationHeaderTokenFinder;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\BearerTokenType\QueryStringTokenFinder;
use OAuth2Framework\Component\BearerTokenType\RequestBodyTokenFinder;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\AccessToken;

/**
 * @internal
 */
final class BearerTokenTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericCalls(): void
    {
        $bearerToken = BearerToken::create('TEST')
            ->addTokenFinder(AuthorizationHeaderTokenFinder::create())
        ;

        static::assertSame('Bearer', $bearerToken->name());
        static::assertSame('Bearer realm="TEST"', $bearerToken->getScheme());
        static::assertSame([], $bearerToken->getAdditionalInformation());
    }

    /**
     * @test
     */
    public function anAccessTokenInTheAuthorizationHeaderIsFound(): void
    {
        $bearerToken = BearerToken::create('TEST')
            ->addTokenFinder(AuthorizationHeaderTokenFinder::create())
        ;
        $request = $this->buildRequest('GET', [], [
            'AUTHORIZATION' => 'Bearer ACCESS_TOKEN_ID',
        ]);

        $additionalCredentialValues = [];
        static::assertSame('ACCESS_TOKEN_ID', $bearerToken->find($request, $additionalCredentialValues));
    }

    /**
     * @test
     */
    public function noAccessTokenInTheAuthorizationHeaderIsFound(): void
    {
        $bearerToken = BearerToken::create('TEST')
            ->addTokenFinder(AuthorizationHeaderTokenFinder::create())
        ;
        $request = $this->buildRequest('GET', [], [
            'AUTHORIZATION' => 'MAC FOO_MAC_TOKEN',
        ]);

        $additionalCredentialValues = [];
        static::assertNull($bearerToken->find($request, $additionalCredentialValues));
    }

    /**
     * @test
     */
    public function anAccessTokenInTheQueryStringIsFound(): void
    {
        $bearerToken = BearerToken::create('TEST')
            ->addTokenFinder(QueryStringTokenFinder::create())
        ;
        $request = $this->buildRequest('GET', [], [], [
            'access_token' => 'ACCESS_TOKEN_ID',
        ]);

        $additionalCredentialValues = [];
        static::assertSame('ACCESS_TOKEN_ID', $bearerToken->find($request, $additionalCredentialValues));
    }

    /**
     * @test
     */
    public function anAccessTokenInTheRequestBodyIsFound(): void
    {
        $bearerToken = BearerToken::create('TEST')
            ->addTokenFinder(RequestBodyTokenFinder::create())
        ;
        $request = $this->buildRequest('GET', [
            'access_token' => 'ACCESS_TOKEN_ID',
        ]);

        $additionalCredentialValues = [];
        static::assertSame('ACCESS_TOKEN_ID', $bearerToken->find($request, $additionalCredentialValues));
    }

    /**
     * @test
     */
    public function iFoundAValidAccessToken(): void
    {
        $bearerToken = BearerToken::create('TEST')
            ->addTokenFinder(AuthorizationHeaderTokenFinder::create())
        ;
        $additionalCredentialValues = [];
        $accessToken = AccessToken::create(
            AccessTokenId::create('ACCESS_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            new DateTimeImmutable('now'),
            DataBag::create([
                'token_type' => 'Bearer',
            ]),
            DataBag::create(),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $request = $this->buildRequest('GET', []);

        static::assertTrue($bearerToken->isRequestValid($accessToken, $request, $additionalCredentialValues));
    }

    /**
     * @test
     */
    public function iFoundAnInvalidAccessToken(): void
    {
        $bearerToken = BearerToken::create('TEST')
            ->addTokenFinder(AuthorizationHeaderTokenFinder::create())
        ;
        $additionalCredentialValues = [];
        $accessToken = AccessToken::create(
            AccessTokenId::create('ACCESS_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            new DateTimeImmutable('now'),
            DataBag::create([
                'token_type' => 'MAC',
            ]),
            DataBag::create(),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );

        $request = $this->buildRequest();

        static::assertFalse($bearerToken->isRequestValid($accessToken, $request, $additionalCredentialValues));
    }
}
