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
use OAuth2Framework\Component\ClientAuthentication\None;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpoint
 * @group ClientAuthentication
 */
class NoneAuthenticationMethodTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $method = new None();

        self::assertEquals([], $method->getSchemesParameters());
        self::assertEquals(['none'], $method->getSupportedMethods());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest()
    {
        $method = new None();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        self::assertNull($clientId);
        self::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdHasBeenFoundInTheRequest()
    {
        $method = new None();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['client_id' => 'CLIENT_ID']);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        self::assertInstanceOf(ClientId::class, $clientId);
        self::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIsAuthenticated()
    {
        $method = new None();
        $request = $this->prophesize(ServerRequestInterface::class);
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        self::assertTrue($method->isClientAuthenticated($client, null, $request->reveal()));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeChecked()
    {
        $method = new None();
        $parameters = DataBag::create([]);
        $validatedParameters = DataBag::create([]);

        self::assertSame($validatedParameters, $method->checkClientConfiguration($parameters, $validatedParameters));
    }
}
