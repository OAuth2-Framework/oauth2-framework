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

namespace OAuth2Framework\Component\ClientAuthentication\Tests;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretPost;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @group TokenEndpoint
 * @group ClientAuthentication
 */
final class ClientSecretPostAuthenticationMethodTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $method = new ClientSecretPost();

        self::assertEquals([], $method->getSchemesParameters());
        self::assertEquals(['client_secret_post'], $method->getSupportedMethods());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest()
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest([]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        self::assertNull($clientId);
        self::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdHasBeenFoundInTheRequestButNoClientSecret()
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest(['client_id' => 'CLIENT_ID']);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        self::assertNull($clientId);
        self::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdAndClientSecretHaveBeenFoundInTheRequest()
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest([
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        self::assertInstanceOf(ClientId::class, $clientId);
        self::assertEquals('CLIENT_SECRET', $credentials);
    }

    /**
     * @test
     */
    public function theClientIsAuthenticated()
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest([
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'client_secret' => 'CLIENT_SECRET',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        self::assertTrue($method->isClientAuthenticated($client, 'CLIENT_SECRET', $request->reveal()));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeChecked()
    {
        $method = new ClientSecretPost();
        $validatedParameters = $method->checkClientConfiguration(DataBag::create([]), DataBag::create([]));

        self::assertTrue($validatedParameters->has('client_secret'));
        self::assertTrue($validatedParameters->has('client_secret_expires_at'));
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()->willReturn(http_build_query($data));
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')->willReturn(true);
        $request->getHeader('Content-Type')->willReturn(['application/x-www-form-urlencoded']);
        $request->getBody()->willReturn($body->reveal());
        $request->getParsedBody()->willReturn([]);

        return $request;
    }
}
