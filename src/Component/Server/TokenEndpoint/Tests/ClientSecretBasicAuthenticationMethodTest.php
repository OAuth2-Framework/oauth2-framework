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
use OAuth2Framework\Component\Server\TokenEndpoint\AuthenticationMethod\ClientSecretBasic;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpoint
 * @group AuthenticationMethod
 */
final class ClientSecretBasicAuthenticationMethodTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $method = new ClientSecretBasic('My Service');

        self::assertEquals(['Basic realm="My Service",charset="UTF-8"'], $method->getSchemesParameters());
        self::assertEquals(['client_secret_basic'], $method->getSupportedAuthenticationMethods());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest()
    {
        $method = new ClientSecretBasic('My Service');
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader("Authorization")->willReturn(null);

        $clientId = $method->findClientId($request->reveal(), $credentials);
        self::assertNull($clientId);
        self::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdAndClientSecretHaveBeenFoundInTheRequest()
    {
        $method = new ClientSecretBasic('My Service');
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader("Authorization")->willReturn(['Basic '.base64_encode('CLIENT_ID:CLIENT_SECRET')]);

        $clientId = $method->findClientId($request->reveal(), $credentials);
        self::assertInstanceOf(ClientId::class, $clientId);
        self::assertEquals('CLIENT_SECRET', $credentials);
    }

    /**
     * @test
     */
    public function theClientIsAuthenticated()
    {
        $method = new ClientSecretBasic('My Service');
        $request = $this->prophesize(ServerRequestInterface::class);
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'client_secret' => 'CLIENT_SECRET'
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
        $method = new ClientSecretBasic('My Service');
        $validatedParameters = $method->checkClientConfiguration(DataBag::create([]), DataBag::create([]));

        self::assertTrue($validatedParameters->has('client_secret'));
        self::assertTrue($validatedParameters->has('client_secret_expires_at'));
    }
}
