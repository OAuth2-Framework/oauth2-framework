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

namespace OAuth2Framework\Component\Server\TokenEndpoint\Tests;

use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\TokenEndpoint\AuthenticationMethod\ClientSecretPost;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpoint
 * @group AuthenticationMethod
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
        self::assertEquals(['client_secret_post'], $method->getSupportedAuthenticationMethods());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest()
    {
        $method = new ClientSecretPost();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([]);

        $clientId = $method->findClientId($request->reveal(), $credentials);
        self::assertNull($clientId);
        self::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdHasBeenFoundInTheRequestButNoClientSecret()
    {
        $method = new ClientSecretPost();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['client_id' => 'CLIENT_ID']);

        $clientId = $method->findClientId($request->reveal(), $credentials);
        self::assertNull($clientId);
        self::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdAndClientSecretHaveBeenFoundInTheRequest()
    {
        $method = new ClientSecretPost();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        $clientId = $method->findClientId($request->reveal(), $credentials);
        self::assertInstanceOf(ClientId::class, $clientId);
        self::assertEquals('CLIENT_SECRET', $credentials);
    }

    /**
     * @test
     */
    public function theClientIsAuthenticated()
    {
        $method = new ClientSecretPost();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
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
}
