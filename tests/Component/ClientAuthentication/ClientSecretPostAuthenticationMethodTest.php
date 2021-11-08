<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientAuthentication;

use OAuth2Framework\Component\ClientAuthentication\ClientSecretPost;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class ClientSecretPostAuthenticationMethodTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericCalls(): void
    {
        $method = new ClientSecretPost();

        static::assertSame([], $method->getSchemesParameters());
        static::assertSame(['client_secret_post'], $method->getSupportedMethods());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest(): void
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest('GET', []);

        $clientId = $method->findClientIdAndCredentials($request, $credentials);
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdHasBeenFoundInTheRequestButNoClientSecret(): void
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest('GET', [
            'client_id' => 'CLIENT_ID',
        ]);

        $clientId = $method->findClientIdAndCredentials($request, $credentials);
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdAndClientSecretHaveBeenFoundInTheRequest(): void
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest('GET', [
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        $clientId = $method->findClientIdAndCredentials($request, $credentials);
        static::assertInstanceOf(ClientId::class, $clientId);
        static::assertSame('CLIENT_SECRET', $credentials);
    }

    /**
     * @test
     */
    public function theClientIsAuthenticated(): void
    {
        $method = new ClientSecretPost();
        $request = $this->buildRequest('GET', [
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_basic',
                'client_secret' => 'CLIENT_SECRET',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        static::assertTrue($method->isClientAuthenticated($client, 'CLIENT_SECRET', $request));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeChecked(): void
    {
        $method = new ClientSecretPost();
        $validatedParameters = $method->checkClientConfiguration(DataBag::create([]), DataBag::create([]));

        static::assertTrue($validatedParameters->has('client_secret'));
        static::assertTrue($validatedParameters->has('client_secret_expires_at'));
    }
}
