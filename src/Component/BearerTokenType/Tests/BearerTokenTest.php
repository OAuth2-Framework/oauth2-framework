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

namespace OAuth2Framework\Component\BearerTokenType\Tests;

use OAuth2Framework\Component\BearerTokenType\AuthorizationHeaderTokenFinder;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\BearerTokenType\QueryStringTokenFinder;
use OAuth2Framework\Component\BearerTokenType\RequestBodyTokenFinder;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @group BearerToken
 */
final class BearerTokenTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $bearerToken = new BearerToken('TEST');
        $bearerToken->addTokenFinder(new AuthorizationHeaderTokenFinder());

        static::assertEquals('Bearer', $bearerToken->name());
        static::assertEquals('Bearer realm="TEST"', $bearerToken->getScheme());
        static::assertEquals([], $bearerToken->getAdditionalInformation());
    }

    /**
     * @test
     */
    public function anAccessTokenInTheAuthorizationHeaderIsFound()
    {
        $bearerToken = new BearerToken('TEST');
        $bearerToken->addTokenFinder(new AuthorizationHeaderTokenFinder());
        $request = $this->buildRequest([]);
        $request->getHeader('AUTHORIZATION')->willReturn(['Bearer ACCESS_TOKEN_ID']);

        $additionalCredentialValues = [];
        static::assertEquals('ACCESS_TOKEN_ID', $bearerToken->find($request->reveal(), $additionalCredentialValues));
    }

    /**
     * @test
     */
    public function noAccessTokenInTheAuthorizationHeaderIsFound()
    {
        $bearerToken = new BearerToken('TEST');
        $bearerToken->addTokenFinder(new AuthorizationHeaderTokenFinder());
        $request = $this->buildRequest([]);
        $request->getHeader('AUTHORIZATION')->willReturn(['MAC FOO_MAC_TOKEN']);

        $additionalCredentialValues = [];
        static::assertNull($bearerToken->find($request->reveal(), $additionalCredentialValues));
    }

    /**
     * @test
     */
    public function anAccessTokenInTheQueryStringIsFound()
    {
        $bearerToken = new BearerToken('TEST');
        $bearerToken->addTokenFinder(new QueryStringTokenFinder());
        $request = $this->buildRequest([]);
        $request->getQueryParams()->willReturn(['access_token' => 'ACCESS_TOKEN_ID']);

        $additionalCredentialValues = [];
        static::assertEquals('ACCESS_TOKEN_ID', $bearerToken->find($request->reveal(), $additionalCredentialValues));
    }

    /**
     * @test
     */
    public function anAccessTokenInTheRequestBodyIsFound()
    {
        $bearerToken = new BearerToken('TEST');
        $bearerToken->addTokenFinder(new RequestBodyTokenFinder());
        $request = $this->buildRequest([]);
        $request->getParsedBody()->willReturn(['access_token' => 'ACCESS_TOKEN_ID']);

        $additionalCredentialValues = [];
        static::assertEquals('ACCESS_TOKEN_ID', $bearerToken->find($request->reveal(), $additionalCredentialValues));
    }

    /**
     * @test
     */
    public function iFoundAValidAccessToken()
    {
        $bearerToken = new BearerToken('TEST');
        $bearerToken->addTokenFinder(new AuthorizationHeaderTokenFinder());
        $additionalCredentialValues = [];
        $accessToken = new AccessToken(
            new AccessTokenId('ACCESS_TOKEN_ID'),
            new ClientId('CLIENT_ID'),
            new ClientId('CLIENT_ID'),
            new \DateTimeImmutable('now'),
            new DataBag(['token_type' => 'Bearer']),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $request = $this->buildRequest([]);

        static::assertTrue($bearerToken->isRequestValid($accessToken, $request->reveal(), $additionalCredentialValues));
    }

    /**
     * @test
     */
    public function iFoundAnInvalidAccessToken()
    {
        $bearerToken = new BearerToken('TEST');
        $bearerToken->addTokenFinder(new AuthorizationHeaderTokenFinder());
        $additionalCredentialValues = [];
        $accessToken = new AccessToken(
            new AccessTokenId('ACCESS_TOKEN_ID'),
            new ClientId('CLIENT_ID'),
            new ClientId('CLIENT_ID'),
            new \DateTimeImmutable('now'),
            new DataBag(['token_type' => 'MAC']),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $request = $this->prophesize(ServerRequestInterface::class);

        static::assertFalse($bearerToken->isRequestValid($accessToken, $request->reveal(), $additionalCredentialValues));
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()->willReturn(\http_build_query($data));
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')->willReturn(true);
        $request->getHeader('Content-Type')->willReturn(['application/x-www-form-urlencoded']);
        $request->getBody()->willReturn($body->reveal());
        $request->getParsedBody()->willReturn([]);

        return $request;
    }
}
