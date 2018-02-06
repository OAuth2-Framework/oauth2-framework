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
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretBasic;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpoint
 * @group ClientAuthentication
 */
class ClientSecretBasicAuthenticationMethodTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $method = new ClientSecretBasic('My Service');

        self::assertEquals(['Basic realm="My Service",charset="UTF-8"'], $method->getSchemesParameters());
        self::assertEquals(['client_secret_basic'], $method->getSupportedMethods());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest()
    {
        $manager = new AuthenticationMethodManager();
        $manager->add(new ClientSecretBasic('My Service'));
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn([]);

        $clientId = $manager->findClientIdAndCredentials($request->reveal(), $credentials);
        self::assertNull($clientId);
        self::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdAndClientSecretHaveBeenFoundInTheRequest()
    {
        $manager = new AuthenticationMethodManager();
        $manager->add(new ClientSecretBasic('My Service'));
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn(['Basic '.base64_encode('CLIENT_ID:CLIENT_SECRET')]);

        $clientId = $manager->findClientIdAndCredentials($request->reveal(), $method, $credentials);
        self::assertInstanceOf(ClientSecretBasic::class, $method);
        self::assertInstanceOf(ClientId::class, $clientId);
        self::assertEquals('CLIENT_SECRET', $credentials);

        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'client_secret' => 'CLIENT_SECRET',
                'token_endpoint_auth_method' => 'client_secret_basic',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        self::assertTrue($manager->isClientAuthenticated($request->reveal(), $client, $method, 'CLIENT_SECRET'));
    }

    /**
     * @test
     */
    public function theClientUsesAnotherAuthenticationMethod()
    {
        $method = new ClientSecretBasic('My Service');
        $manager = new AuthenticationMethodManager();
        $manager->add($method);
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'client_secret' => 'CLIENT_SECRET',
                'token_endpoint_auth_method' => 'client_secret_post',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        self::assertFalse($manager->isClientAuthenticated($request->reveal(), $client, $method, 'CLIENT_SECRET'));
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
