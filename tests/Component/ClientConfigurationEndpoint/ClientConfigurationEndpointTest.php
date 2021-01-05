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

namespace OAuth2Framework\Tests\Component\ClientConfigurationEndpoint;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\BearerTokenType\AuthorizationHeaderTokenFinder;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientConfigurationEndpoint\ClientConfigurationEndpoint;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group ClientConfigurationEndpoint
 *
 * @internal
 */
final class ClientConfigurationEndpointTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var null|ClientConfigurationEndpoint
     */
    private $clientConfigurationEndpoint;

    /**
     * @var null|ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @test
     */
    public function theClientConfigurationEndpointCanReceiveGetRequestsAndRetrieveClientInformation()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->has('registration_access_token')->willReturn(true);
        $client->get('registration_access_token')->willReturn('REGISTRATION_TOKEN');
        $client->all()->willReturn([
            'registration_access_token' => 'REGISTRATION_TOKEN',
            'client_id' => 'CLIENT_ID',
        ]);

        $clientRepository = $this->prophesize(ClientRepository::class);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn('GET');
        $request->getAttribute('client')->willReturn($client->reveal());
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
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->has('registration_access_token')->willReturn(true);
        $client->get('registration_access_token')->willReturn('REGISTRATION_TOKEN');
        $client->all()->willReturn([
            'client_id' => 'CLIENT_ID',
        ]);
        $client->setParameter(Argument::type(DataBag::class))->will(function () {});

        $clientRepository = $this->prophesize(ClientRepository::class);
        $clientRepository->save(Argument::type(Client::class))->shouldBeCalled();

        $request = $this->buildRequest([]);
        $request->getMethod()->willReturn('PUT');
        $request->getAttribute('client')->willReturn($client->reveal());
        $request->getHeader('AUTHORIZATION')->willReturn(['Bearer REGISTRATION_TOKEN']);

        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $this->getClientConfigurationEndpoint($clientRepository->reveal())->process($request->reveal(), $handler->reveal());
        $response->getBody()->rewind();
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('{"client_id":"CLIENT_ID"}', $response->getBody()->getContents());
    }

    private function getClientConfigurationEndpoint(ClientRepository $clientRepository): ClientConfigurationEndpoint
    {
        if (null === $this->clientConfigurationEndpoint) {
            $bearerToken = new BearerToken('Client Manager');
            $bearerToken->addTokenFinder(new AuthorizationHeaderTokenFinder());
            $this->clientConfigurationEndpoint = new ClientConfigurationEndpoint(
                $clientRepository,
                $bearerToken,
                $this->getResponseFactory(),
                new RuleManager()
            );
        }

        return $this->clientConfigurationEndpoint;
    }

    private function getResponseFactory(): ResponseFactoryInterface
    {
        if (null === $this->responseFactory) {
            $this->responseFactory = new Psr17Factory();
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
