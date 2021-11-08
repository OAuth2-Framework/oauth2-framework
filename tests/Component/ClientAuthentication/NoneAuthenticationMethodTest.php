<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientAuthentication;

use OAuth2Framework\Component\ClientAuthentication\None;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class NoneAuthenticationMethodTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericCalls(): void
    {
        $method = new None();

        static::assertSame([], $method->getSchemesParameters());
        static::assertSame(['none'], $method->getSupportedMethods());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest(): void
    {
        $method = new None();
        $request = $this->buildRequest('GET', []);

        $clientId = $method->findClientIdAndCredentials($request, $credentials);
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdHasBeenFoundInTheRequest(): void
    {
        $method = new None();
        $request = $this->buildRequest('GET', [
            'client_id' => 'CLIENT_ID',
        ]);

        $clientId = $method->findClientIdAndCredentials($request, $credentials);
        static::assertInstanceOf(ClientId::class, $clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIsAuthenticated(): void
    {
        $method = new None();

        $request = $this->buildRequest();

        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_endpoint_auth_method' => 'none',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        static::assertTrue($method->isClientAuthenticated($client, null, $request));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeChecked(): void
    {
        $method = new None();
        $parameters = DataBag::create([]);
        $validatedParameters = DataBag::create([]);

        static::assertSame($validatedParameters, $method->checkClientConfiguration($parameters, $validatedParameters));
    }
}
