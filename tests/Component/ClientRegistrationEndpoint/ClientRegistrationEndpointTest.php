<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRegistrationEndpoint;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\ClientRegistrationEndpoint\ClientRegistrationEndpoint;
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

/**
 * @internal
 */
final class ClientRegistrationEndpointTest extends TestCase
{
    use ProphecyTrait;

    private ?ClientRegistrationEndpoint $clientRegistrationEndpoint = null;

    private ?Psr17Factory $responseFactory = null;

    /**
     * @test
     */
    public function theClientRegistrationEndpointCanReceiveRegistrationRequests(): void
    {
        $request = $this->buildRequest([]);
        $request->getMethod()
            ->willReturn('POST')
        ;
        $request->getAttribute('initial_access_token')
            ->willReturn(null)
        ;

        $response = $this->getClientRegistrationEndpoint()
            ->process($request->reveal())
        ;

        static::assertSame(201, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertSame('{"client_id":"CLIENT_ID"}', $response->getBody()->getContents());
    }

    private function getClientRegistrationEndpoint(): ClientRegistrationEndpoint
    {
        if ($this->clientRegistrationEndpoint === null) {
            $client = $this->prophesize(Client::class);
            $client->isPublic()
                ->willReturn(false)
            ;
            $client->getPublicId()
                ->willReturn(new ClientId('CLIENT_ID'))
            ;
            $client->getClientId()
                ->willReturn(new ClientId('CLIENT_ID'))
            ;

            $clientRepository = $this->prophesize(ClientRepository::class);
            $clientRepository->find(Argument::type(ClientId::class))->willReturn($client->reveal());
            $clientRepository->save(Argument::type(Client::class))->will(function (array $args) {});
            $clientRepository->createClientId()
                ->willReturn(new ClientId('CLIENT_ID'))
            ;

            $client = $this->prophesize(Client::class);
            $clientRepository->create(
                Argument::type(ClientId::class),
                Argument::type(DataBag::class),
                Argument::any()
            )->will(
                function (array $args) use ($client) {
                    $client->isPublic()
                        ->willReturn(false)
                ;
                    $client->getPublicId()
                        ->willReturn($args[0])
                ;
                    $client->getClientId()
                        ->willReturn($args[0])
                ;
                    $client->getOwnerId()
                        ->willReturn($args[2])
                ;
                    $client->all()
                        ->willReturn(($args[1])->all() + [
                            'client_id' => (string) $args[0],
                        ])
                ;

                    return $client->reveal();
                }
            );

            $this->clientRegistrationEndpoint = new ClientRegistrationEndpoint(
                $clientRepository->reveal(),
                $this->getResponseFactory(),
                new RuleManager()
            );
        }

        return $this->clientRegistrationEndpoint;
    }

    private function getResponseFactory(): ResponseFactoryInterface
    {
        if ($this->responseFactory === null) {
            $this->responseFactory = new Psr17Factory();
        }

        return $this->responseFactory;
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()
            ->willReturn(json_encode($data))
        ;
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')
            ->willReturn(true)
        ;
        $request->getHeader('Content-Type')
            ->willReturn(['application/json'])
        ;
        $request->getBody()
            ->willReturn($body->reveal())
        ;
        $request->getParsedBody()
            ->willReturn([])
        ;

        return $request;
    }
}
