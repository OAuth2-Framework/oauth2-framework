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

namespace OAuth2Framework\Component\ClientConfigurationEndpoint\Tests;

use Http\Message\MessageFactory\DiactorosMessageFactory;
use Http\Message\ResponseFactory;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientConfigurationEndpoint\ClientConfigurationEndpoint;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group ClientConfigurationEndpoint
 */
final class ClientConfigurationEndpointTest extends TestCase
{
    /**
     * @test
     */
    public function theClientConfigurationEndpointCanReceiveGetRequestsAndRetrieveClientInformation()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([
                'registration_access_token' => 'REGISTRATION_TOKEN',
            ]),
            null
        );
        $clientRepository = $this->prophesize(ClientRepository::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('GET');
        $request->getAttribute('client')->willReturn($client);
        $request->getHeader('AUTHORIZATION')->willReturn(['Bearer REGISTRATION_TOKEN']);

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $this->getClientConfigurationEndpoint($clientRepository->reveal())->process($request->reveal(), $handler->reveal());
        $response->getBody()->rewind();
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('{"registration_access_token":"REGISTRATION_TOKEN","client_id":"CLIENT_ID"}', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theClientConfigurationEndpointCanReceivePutRequestsAndUpdateTheClient()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([
                'registration_access_token' => 'REGISTRATION_TOKEN',
            ]),
            null
        );
        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->save(Argument::type(Client::class))->shouldBeCalled();

        $request = $this->buildRequest([]);
        $request->getMethod()->willReturn('PUT');
        $request->getAttribute('client')->willReturn($client);
        $request->getHeader('AUTHORIZATION')->willReturn(['Bearer REGISTRATION_TOKEN']);

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $this->getClientConfigurationEndpoint($clientRepository->reveal())->process($request->reveal(), $handler->reveal());
        $response->getBody()->rewind();
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('{"client_id":"CLIENT_ID"}', $response->getBody()->getContents());
    }

    /**
     * @var null|ClientConfigurationEndpoint
     */
    private $clientConfigurationEndpoint = null;

    private function getClientConfigurationEndpoint(ClientRepository $clientRepository): ClientConfigurationEndpoint
    {
        if (null === $this->clientConfigurationEndpoint) {
            $this->clientConfigurationEndpoint = new ClientConfigurationEndpoint(
                $clientRepository,
                new BearerToken('Client Manager', true, false, false),
                $this->getResponseFactory(),
                new RuleManager()
            );
        }

        return $this->clientConfigurationEndpoint;
    }

    /**
     * @var ResponseFactory|null
     */
    private $responseFactory = null;

    private function getResponseFactory(): ResponseFactory
    {
        if (null === $this->responseFactory) {
            $this->responseFactory = new DiactorosMessageFactory();
        }

        return $this->responseFactory;
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()->willReturn(\Safe\json_encode($data));
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')->willReturn(true);
        $request->getHeader('Content-Type')->willReturn(['application/json']);
        $request->getBody()->willReturn($body->reveal());
        $request->getParsedBody()->willReturn([]);

        return $request;
    }
}
