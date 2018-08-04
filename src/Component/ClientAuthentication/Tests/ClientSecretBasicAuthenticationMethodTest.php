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

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretBasic;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpoint
 * @group ClientAuthentication
 */
final class ClientSecretBasicAuthenticationMethodTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $method = new ClientSecretBasic('My Service');

        static::assertEquals(['Basic realm="My Service",charset="UTF-8"'], $method->getSchemesParameters());
        static::assertEquals(['client_secret_basic'], $method->getSupportedMethods());
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
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdAndClientSecretHaveBeenFoundInTheRequest()
    {
        $manager = new AuthenticationMethodManager();
        $manager->add(new ClientSecretBasic('My Service'));
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn(['Basic '.\base64_encode('CLIENT_ID:CLIENT_SECRET')]);

        $clientId = $manager->findClientIdAndCredentials($request->reveal(), $method, $credentials);
        static::assertInstanceOf(ClientSecretBasic::class, $method);
        static::assertInstanceOf(ClientId::class, $clientId);
        static::assertEquals('CLIENT_SECRET', $credentials);

        $client = Client::createEmpty();
        $client = $client->create(
            new ClientId('CLIENT_ID'),
            new DataBag([
                'client_secret' => 'CLIENT_SECRET',
                'token_endpoint_auth_method' => 'client_secret_basic',
            ]),
            new UserAccountId('USER_ACCOUNT_ID')
        );

        static::assertTrue($manager->isClientAuthenticated($request->reveal(), $client, $method, 'CLIENT_SECRET'));
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
            new ClientId('CLIENT_ID'),
            new DataBag([
                'client_secret' => 'CLIENT_SECRET',
                'token_endpoint_auth_method' => 'client_secret_post',
            ]),
            new UserAccountId('USER_ACCOUNT_ID')
        );
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        static::assertFalse($manager->isClientAuthenticated($request->reveal(), $client, $method, 'CLIENT_SECRET'));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeChecked()
    {
        $method = new ClientSecretBasic('My Service');
        $validatedParameters = $method->checkClientConfiguration(new DataBag([]), new DataBag([]));

        static::assertTrue($validatedParameters->has('client_secret'));
        static::assertTrue($validatedParameters->has('client_secret_expires_at'));
    }
}
