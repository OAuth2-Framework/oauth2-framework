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

namespace OAuth2Framework\Component\ClientRegistrationEndpoint\Tests;

use Http\Message\MessageFactory\DiactorosMessageFactory;
use Http\Message\ResponseFactory;
use OAuth2Framework\Component\ClientRegistrationEndpoint\ClientRegistrationEndpoint;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use SimpleBus\Message\Bus\MessageBus;

/**
 * @group ClientRegistrationEndpoint
 */
class ClientRegistrationEndpointTest extends TestCase
{
    /**
     * @test
     */
    public function theClientRegistrationEndpointCanReceiveRegistrationRequests()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('POST');
        $request->getAttribute('initial_access_token')->willReturn(null);
        $request->getParsedBody()->willReturn([]);

        $response = $this->getClientRegistrationEndpoint()->process($request->reveal());

        self::assertEquals(201, $response->getStatusCode());
        $response->getBody()->rewind();
        self::assertEquals('{"client_id":"CLIENT_ID"}', $response->getBody()->getContents());
    }

    /**
     * @var null|ClientRegistrationEndpoint
     */
    private $clientRegistrationEndpoint = null;

    /**
     * @return ClientRegistrationEndpoint
     */
    private function getClientRegistrationEndpoint(): ClientRegistrationEndpoint
    {
        if (null === $this->clientRegistrationEndpoint) {
            $client = Client::createEmpty();
            $client = $client->create(
                ClientId::create('CLIENT_ID'),
                DataBag::create([]),
                null
            );
            $clientRepository = $this->prophesize(ClientRepository::class);
            $clientRepository->find(Argument::type(ClientId::class))->willReturn($client);
            $messageBus = $this->prophesize(MessageBus::class);

            $this->clientRegistrationEndpoint = new ClientRegistrationEndpoint(
                $clientRepository->reveal(),
                $this->getResponseFactory(),
                $messageBus->reveal(),
                new RuleManager()
            );
        }

        return $this->clientRegistrationEndpoint;
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
