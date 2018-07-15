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

namespace OAuth2Framework\Component\MacTokenType\Tests;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @group MacToken
 */
final class MacTokenTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $macToken = new FooMacToken('hmac-sha-256', 30);

        self::assertEquals('MAC', $macToken->name());
        self::assertEquals('MAC', $macToken->getScheme());
        self::assertEquals(['mac_key' => 'MAC_KEY_FOO_BAR', 'mac_algorithm' => 'hmac-sha-256'], $macToken->getAdditionalInformation());
    }

    /**
     * @test
     */
    public function anAccessTokenInTheAuthorizationHeaderIsFound()
    {
        $macToken = new FooMacToken('hmac-sha-256', 30);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('AUTHORIZATION')->willReturn(['MAC id="h480djs93hd8",ts="1336363200",nonce="dj83hs9s",mac="bhCQXTVyfj5cmA9uKkPFx1zeOXM="']);

        $additionalCredentialValues = [];
        self::assertEquals('h480djs93hd8', $macToken->find($request->reveal(), $additionalCredentialValues));
        self::assertEquals(['id' => 'h480djs93hd8', 'ts' => '1336363200', 'nonce' => 'dj83hs9s', 'mac' => 'bhCQXTVyfj5cmA9uKkPFx1zeOXM='], $additionalCredentialValues);
    }

    /**
     * @test
     */
    public function iFoundAValidAccessToken()
    {
        $macToken = new FooMacToken('hmac-sha-256', 30);
        $accessToken = AccessToken::createEmpty();
        $mac = $this->generateMac(
            'sha256',
            'adijq39jdlaska9asud',
            \time(),
            'dj83hs9s',
            'POST',
            '/request?b5=%3D%253D&a3=a&c%40=&a2=r%20b&c2&a3=2+q',
            'example.com',
            80,
            null
        );
        $additionalCredentialValues = ['id' => 'h480djs93hd8', 'ts' => \time(), 'nonce' => 'dj83hs9s', 'mac' => $mac];
        $accessToken = $accessToken->create(
            AccessTokenId::create('h480djs93hd8'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            DataBag::create(['token_type' => 'MAC', 'mac_key' => 'adijq39jdlaska9asud', 'mac_algorithm' => 'hmac-sha-256']),
            DataBag::create([]),
            new \DateTimeImmutable('now'),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $uri = $this->prophesize(UriInterface::class);
        $uri->getHost()->willReturn('example.com');
        $uri->getPort()->willReturn(80);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->willReturn($uri->reveal());
        $request->getMethod()->willReturn('POST');
        $request->getRequestTarget()->willReturn('/request?b5=%3D%253D&a3=a&c%40=&a2=r%20b&c2&a3=2+q');

        self::assertTrue($macToken->isRequestValid($accessToken, $request->reveal(), $additionalCredentialValues));
    }

    /**
     * @param string      $algorithm
     * @param string      $key
     * @param int         $timestamp
     * @param string      $nonce
     * @param string      $method
     * @param string      $requestUri
     * @param string      $host
     * @param int         $port
     * @param null|string $ext
     *
     * @return string
     */
    private function generateMac(string $algorithm, string $key, int $timestamp, string $nonce, string $method, string $requestUri, string $host, int $port, ?string $ext): string
    {
        $basestr = \sprintf("%d\n%s\n%s\n%s\n%s\n%s\n%s\n", $timestamp, $nonce, $method, $requestUri, $host, $port, $ext);

        return \base64_encode(\hash_hmac($algorithm, $basestr, $key, true));
    }
}
