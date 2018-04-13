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
use Psr\Http\Server\RequestHandlerInterface;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientConfigurationEndpoint\ClientConfigurationEndpoint;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Client\Command\DeleteClientCommand;
use OAuth2Framework\Component\Core\Client\Command\UpdateClientCommand;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use SimpleBus\Message\Bus\MessageBus;

/**
 * @group ClientConfigurationEndpoint
 */
class ClientConfigurationEndpointTest extends TestCase
{
    /**
     * @test
     */
    public function theClientConfigurationEndpointCanReceiveGetRequestsAndRetrieveClientInformation()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
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
        $messageBus = $this->prophesize(MessageBus::class);

        $response = $this->getClientConfigurationEndpoint($clientRepository->reveal(), $messageBus->reveal())->process($request->reveal(), $handler->reveal());
        $response->getBody()->rewind();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('{"registration_access_token":"REGISTRATION_TOKEN","client_id":"CLIENT_ID"}', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theClientConfigurationEndpointCanReceivePutRequestsAndSendUpdateCommandsToTheCommandHandler()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'registration_access_token' => 'REGISTRATION_TOKEN',
            ]),
            null
        );
        $updatedClient = $client->withParameters(DataBag::create([
            'registration_access_token' => 'NEW_REGISTRATION_TOKEN',
            'foo' => 'bar',
        ]));
        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->find(Argument::type(ClientId::class))->willReturn($updatedClient);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('PUT');
        $request->getAttribute('client')->willReturn($client);
        $request->getParsedBody()->willReturn([]);
        $request->getHeader('AUTHORIZATION')->willReturn(['Bearer REGISTRATION_TOKEN']);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $messageBus = $this->prophesize(MessageBus::class);
        $messageBus->handle(Argument::type(UpdateClientCommand::class))->shouldBeCalled();

        $response = $this->getClientConfigurationEndpoint($clientRepository->reveal(), $messageBus->reveal())->process($request->reveal(), $handler->reveal());
        $response->getBody()->rewind();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('{"registration_access_token":"NEW_REGISTRATION_TOKEN","foo":"bar","client_id":"CLIENT_ID"}', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function theClientConfigurationEndpointCanReceiveDeleteRequestsAndSendDeleteCommandsToTheCommandHandler()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'registration_access_token' => 'REGISTRATION_TOKEN',
            ]),
            null
        );
        $clientRepository = $this->prophesize(ClientRepository::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('DELETE');
        $request->getAttribute('client')->willReturn($client);
        $request->getHeader('AUTHORIZATION')->willReturn(['Bearer REGISTRATION_TOKEN']);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $messageBus = $this->prophesize(MessageBus::class);
        $messageBus->handle(Argument::type(DeleteClientCommand::class))->shouldBeCalled();

        $this->getClientConfigurationEndpoint($clientRepository->reveal(), $messageBus->reveal())->process($request->reveal(), $handler->reveal());
    }

    /**
     * @var null|ClientConfigurationEndpoint
     */
    private $clientConfigurationEndpoint = null;

    /**
     * @param ClientRepository $clientRepository
     *
     * @return ClientConfigurationEndpoint
     */
    private function getClientConfigurationEndpoint(ClientRepository $clientRepository, MessageBus $messageBus): ClientConfigurationEndpoint
    {
        if (null === $this->clientConfigurationEndpoint) {
            $this->clientConfigurationEndpoint = new ClientConfigurationEndpoint(
                $clientRepository,
                new BearerToken('Client Manager', true, false, false),
                $messageBus,
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

    /**
     * @return ResponseFactory
     */
    private function getResponseFactory(): ResponseFactory
    {
        if (null === $this->responseFactory) {
            $this->responseFactory = new DiactorosMessageFactory();
        }

        return $this->responseFactory;
    }
}
