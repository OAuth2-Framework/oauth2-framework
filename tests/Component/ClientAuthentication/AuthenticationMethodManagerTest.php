<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientAuthentication;

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretBasic;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretPost;
use OAuth2Framework\Component\ClientAuthentication\None;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class AuthenticationMethodManagerTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericCalls(): void
    {
        $manager = AuthenticationMethodManager::create()
            ->add(None::create())
            ->add(ClientSecretBasic::create('Realm'))
        ;
        static::assertTrue($manager->has('none'));
        static::assertSame(['none', 'client_secret_basic'], $manager->list());
        static::assertCount(2, $manager->all());
        static::assertSame(['Basic realm="Realm",charset="UTF-8"'], $manager->getSchemesParameters());
    }

    /**
     * @test
     */
    public function theClientCannotUseSeveralAuthenticationMethods(): void
    {
        $manager = AuthenticationMethodManager::create()
            ->add(ClientSecretBasic::create('My Service'))
            ->add(ClientSecretPost::create())
        ;
        $request = $this->buildRequest(
            'GET',
            [
                'client_id' => 'CLIENT_ID',
                'client_secret' => 'CLIENT_SECRET',
            ],
            [
                'Authorization' => 'Basic ' . base64_encode('CLIENT_ID:CLIENT_SECRET'),
            ]
        );

        try {
            $manager->findClientIdAndCredentials($request, $method, $credentials);
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Error $e) {
            static::assertSame(400, $e->getCode());
            static::assertSame([
                'error' => 'invalid_request',
                'error_description' => 'Only one authentication method may be used to authenticate the client.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theClientCanUseSeveralAuthenticationMethodsWhenOneIsNone(): void
    {
        $manager = AuthenticationMethodManager::create()
            ->add(None::create())
            ->add(ClientSecretPost::create())
        ;
        $request = $this->buildRequest('GET', [
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        $clientId = $manager->findClientIdAndCredentials($request, $method, $credentials);
        static::assertInstanceOf(ClientSecretPost::class, $method);
        static::assertInstanceOf(ClientId::class, $clientId);
        static::assertSame('CLIENT_SECRET', $credentials);
    }
}
