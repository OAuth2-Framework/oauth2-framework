<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientAuthentication;

use Nyholm\Psr7\Factory\Psr17Factory;
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
final class ClientAuthenticationMiddlewareTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function aClientIdIsSetButTheClientDoesNotExist(): void
    {
        $request = $this->buildRequest('GET', [], [
            'Authorization' => 'Basic ' . base64_encode('FOO:BAR'),
        ]);

        try {
            $this->getClientAuthenticationMiddleware()
                ->process($request, new TerminalRequestHandler(new Psr17Factory()))
            ;
            static::fail('An OAuth2 exception should be thrown.');
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
    public function aClientIdIsSetButTheClientIsDeleted(): void
    {
        $client = Client::create(
            ClientId::create('FOO'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_basic',
                'client_secret' => 'BAR',
            ]),
            UserAccountId::create('john.1')
        )->markAsDeleted();
        $this->getClientRepository()
            ->save($client)
        ;

        $request = $this->buildRequest('GET', [], [
            'Authorization' => 'Basic ' . base64_encode('FOO:BAR'),
        ]);

        try {
            $this->getClientAuthenticationMiddleware()
                ->process($request, new TerminalRequestHandler(new Psr17Factory()))
            ;
            static::fail('An OAuth2 exception should be thrown.');
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
    public function aClientIdIsSetButTheClientCredentialsExpired(): void
    {
        $client = Client::create(
            ClientId::create('FOO'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_basic',
                'client_secret' => 'BAR',
                'client_secret_expires_at' => time() - 3600,
            ]),
            UserAccountId::create('john.1')
        );
        $this->getClientRepository()
            ->save($client)
        ;

        $request = $this->buildRequest('GET', [], [
            'Authorization' => 'Basic ' . base64_encode('FOO:BAR'),
        ]);

        try {
            $this->getClientAuthenticationMiddleware()
                ->process($request, new TerminalRequestHandler(new Psr17Factory()))
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(401, $e->getCode());
            static::assertSame([
                'error' => 'invalid_client',
                'error_description' => 'Client credentials expired.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function aClientIdIsSetButTheAuthenticationMethodIsNotSupportedByTheClient(): void
    {
        $client = Client::create(
            ClientId::create('FOO'),
            DataBag::create([
                'token_endpoint_auth_method' => 'none',
            ]),
            UserAccountId::create('john.1')
        );
        $this->getClientRepository()
            ->save($client)
        ;

        $request = $this->buildRequest('GET', [], [
            'Authorization' => 'Basic ' . base64_encode('FOO:BAR'),
        ]);

        try {
            $this->getClientAuthenticationMiddleware()
                ->process($request, new TerminalRequestHandler(new Psr17Factory()))
            ;
            static::fail('An OAuth2 exception should be thrown.');
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
    public function aClientIdIsSetButTheClientIsNotAuthenticated(): void
    {
        $client = Client::create(
            ClientId::create('FOO'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_basic',
                'client_secret' => 'BAR',
                'client_secret_expires_at' => time() + 3600,
            ]),
            UserAccountId::create('john.1')
        );
        $this->getClientRepository()
            ->save($client)
        ;

        $request = $this->buildRequest('GET', [], [
            'Authorization' => 'Basic ' . base64_encode('FOO:BAD_SECRET'),
        ]);

        try {
            $this->getClientAuthenticationMiddleware()
                ->process($request, new TerminalRequestHandler(new Psr17Factory()))
            ;
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(401, $e->getCode());
            static::assertSame([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed.',
            ], $e->getData());
        }
    }
}
