<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\TokenEndpoint;

use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Middleware\TerminalRequestHandler;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class TokenEndpointTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function unauthenticatedClient(): void
    {
        $request = $this->buildRequest();
        $request = $request
            ->withAttribute('grant_type', new FooGrantType())
        ;

        try {
            $this->getTokenEndpoint()
                ->process($request, new TerminalRequestHandler())
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(401, $e->getCode());
            static::assertSame([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theClientIsNotAllowedToUseTheGrantType(): void
    {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create(),
            UserAccountId::create('OWNER_ID')
        );

        $request = $this->buildRequest();
        $request = $request
            ->withAttribute('grant_type', new FooGrantType())
            ->withAttribute('client', $client)
        ;

        try {
            $this->getTokenEndpoint()
                ->process($request, new TerminalRequestHandler())
            ;
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'unauthorized_client',
                'error_description' => 'The grant type "foo" is unauthorized for this client.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theTokenRequestIsValidAndAnAccessTokenIsIssued(): void
    {
        $client = Client::create(
            ClientId::create('PUBLIC_CLIENT_ID'),
            DataBag::create([
                'token_endpoint_auth_method' => 'none',
                'grant_types' => ['foo'],
            ]),
            UserAccountId::create('john.1')
        );

        $request = $this->buildRequest(data: [
            'client_id' => 'PUBLIC_CLIENT_ID',
        ]);
        $request = $request
            ->withAttribute('grant_type', new FooGrantType())
            ->withAttribute('client', $client)
            ->withAttribute('token_type', BearerToken::create('REALM'))
        ;

        $response = $this->getTokenEndpoint()
            ->process($request, new TerminalRequestHandler())
        ;
        $response->getBody()
            ->rewind()
        ;
        $body = $response->getBody()
            ->getContents()
        ;

        static::assertSame(200, $response->getStatusCode());
        static::assertMatchesRegularExpression(
            '/^\{"token_type"\:"Bearer","access_token"\:"[a-f0-9]{64}","expires_in"\:\d{4}\}$/',
            $body
        );
    }
}
