<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientAuthentication;

use OAuth2Framework\Component\ClientAuthentication\ClientSecretBasic;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class ClientSecretBasicAuthenticationMethodTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericCalls(): void
    {
        $method = new ClientSecretBasic('My Service');

        static::assertSame(['Basic realm="My Service",charset="UTF-8"'], $method->getSchemesParameters());
        static::assertSame(['client_secret_basic'], $method->getSupportedMethods());
    }

    /**
     * @test
     */
    public function theClientIdCannotBeFoundInTheRequest(): void
    {
        $manager = $this->getAuthenticationMethodManager();
        $manager->add(new ClientSecretBasic('My Service'));
        $request = $this->buildRequest();

        $clientId = $manager->findClientIdAndCredentials($request, $credentials);
        static::assertNull($clientId);
        static::assertNull($credentials);
    }

    /**
     * @test
     */
    public function theClientIdAndClientSecretHaveBeenFoundInTheRequest(): void
    {
        $manager = $this->getAuthenticationMethodManager();
        $manager->add(new ClientSecretBasic('My Service'));

        $request = $this->buildRequest('GET', [], [
            'Authorization' => 'Basic ' . base64_encode('CLIENT_ID:CLIENT_SECRET'),
        ]);

        $clientId = $manager->findClientIdAndCredentials($request, $method, $credentials);
        static::assertInstanceOf(ClientSecretBasic::class, $method);
        static::assertInstanceOf(ClientId::class, $clientId);
        static::assertSame('CLIENT_SECRET', $credentials);

        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_basic',
                'client_secret' => 'CLIENT_SECRET',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        static::assertTrue($manager->isClientAuthenticated($request, $client, $method, 'CLIENT_SECRET'));
    }

    /**
     * @test
     */
    public function theClientUsesAnotherAuthenticationMethod(): void
    {
        $method = new ClientSecretBasic('My Service');
        $manager = $this->getAuthenticationMethodManager();
        $manager->add($method);

        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_post',
                'client_secret' => 'CLIENT_SECRET',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $request = $this->buildRequest('GET', [
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        static::assertFalse($manager->isClientAuthenticated($request, $client, $method, 'CLIENT_SECRET'));
    }

    /**
     * @test
     */
    public function theClientConfigurationCanBeChecked(): void
    {
        $method = new ClientSecretBasic('My Service');
        $validatedParameters = $method->checkClientConfiguration(DataBag::create([]), DataBag::create([]));

        static::assertTrue($validatedParameters->has('client_secret'));
        static::assertTrue($validatedParameters->has('client_secret_expires_at'));
    }
}
