<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientConfigurationEndpoint;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Middleware\TerminalRequestHandler;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class ClientConfigurationEndpointTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function theClientConfigurationEndpointCanReceiveGetRequestsAndRetrieveClientInformation(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'registration_access_token' => 'REGISTRATION_TOKEN',
            ]),
            UserAccountId::create('john.1')
        );

        $request = $this->buildRequest('GET', [], [
            'AUTHORIZATION' => 'Bearer REGISTRATION_TOKEN',
        ]);
        $request = $request->withAttribute('client', $client);

        $response = $this->getClientConfigurationEndpoint()
            ->process($request, new TerminalRequestHandler(new Psr17Factory()))
        ;
        $response->getBody()
            ->rewind()
        ;
        static::assertSame(200, $response->getStatusCode());
        static::assertSame(
            '{"registration_access_token":"REGISTRATION_TOKEN","client_id":"CLIENT_ID"}',
            $response->getBody()
                ->getContents()
        );
    }

    /**
     * @test
     */
    public function theClientConfigurationEndpointCanReceivePutRequestsAndUpdateTheClient(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'registration_access_token' => 'REGISTRATION_TOKEN',
            ]),
            UserAccountId::create('john.1')
        );

        $request = $this->buildRequest(
            method: 'PUT',
            headers: [
                'AUTHORIZATION' => 'Bearer REGISTRATION_TOKEN',
            ],
            contentType: 'application/json'
        );
        $request = $request->withAttribute('client', $client);

        $response = $this->getClientConfigurationEndpoint()
            ->process($request, new TerminalRequestHandler(new Psr17Factory()))
        ;
        $response->getBody()
            ->rewind()
        ;
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('{"client_id":"CLIENT_ID"}', $response->getBody()->getContents());
    }
}
