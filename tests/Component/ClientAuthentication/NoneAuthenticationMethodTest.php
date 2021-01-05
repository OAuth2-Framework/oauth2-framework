<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Tests\Component\ClientAuthentication;

use OAuth2Framework\Component\ClientAuthentication\None;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @group TokenEndpoint
 * @group ClientAuthentication
 *
 * @internal
 */
final class NoneAuthenticationMethodTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function genericCalls()
    {
        $method = new None();

        static::assertEquals([], $method->getSchemesParameters());
        static::assertEquals(['none'], $method->getSupportedMethods());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest()
    {
        $method = new None();
        $request = $this->buildRequest([]);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdHasBeenFoundInTheRequest()
    {
        $method = new None();
        $request = $this->buildRequest(['client_id' => 'CLIENT_ID']);

        $clientId = $method->findClientIdAndCredentials($request->reveal(), $credentials);
        static::assertInstanceOf(ClientId::class, $clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIsAuthenticated()
    {
        $method = new None();
        $request = $this->prophesize(ServerRequestInterface::class);

        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $client->has('token_endpoint_auth_method')->willReturn(true);
        $client->get('token_endpoint_auth_method')->willReturn('none');
        $client->getTokenEndpointAuthenticationMethod()->willReturn('none');
        $client->isDeleted()->willReturn(false);
        $client->areClientCredentialsExpired()->willReturn(false);

        static::assertTrue($method->isClientAuthenticated($client->reveal(), null, $request->reveal()));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeChecked()
    {
        $method = new None();
        $parameters = new DataBag([]);
        $validatedParameters = new DataBag([]);

        static::assertSame($validatedParameters, $method->checkClientConfiguration($parameters, $validatedParameters));
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
