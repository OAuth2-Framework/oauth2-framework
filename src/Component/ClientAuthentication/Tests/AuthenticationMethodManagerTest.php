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

namespace OAuth2Framework\Component\ClientAuthentication\Tests;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethod;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretBasic;
use OAuth2Framework\Component\ClientAuthentication\ClientSecretPost;
use OAuth2Framework\Component\ClientAuthentication\None;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpoint
 * @group ClientAuthentication
 */
class AuthenticationMethodManagerTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $manager = new AuthenticationMethodManager();
        $manager
            ->add(new None())
            ->add(new ClientSecretBasic('Realm'))
        ;
        self::assertTrue($manager->has('none'));
        self::assertEquals(['none', 'client_secret_basic'], $manager->list());
        self::assertInstanceOf(AuthenticationMethod::class, $manager->get('none'));
        self::assertEquals(2, count($manager->all()));
        self::assertEquals(['Basic realm="Realm",charset="UTF-8"'], $manager->getSchemesParameters());
    }

    /**
     * @test
     */
    public function theClientCannotUseSeveralAuthenticationMethods()
    {
        $manager = new AuthenticationMethodManager();
        $manager
            ->add(new ClientSecretBasic('My Service'))
            ->add(new ClientSecretPost())
        ;
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn(['Basic '.base64_encode('CLIENT_ID:CLIENT_SECRET')]);
        $request->getParsedBody()->willReturn([
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        try {
            $manager->findClientIdAndCredentials($request->reveal(), $method, $credentials);
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'Only one authentication method may be used to authenticate the client.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theClientCanUseSeveralAuthenticationMethodsWhenOneIsNone()
    {
        $manager = new AuthenticationMethodManager();
        $manager
            ->add(new None())
            ->add(new ClientSecretPost())
        ;
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET',
        ]);

        $clientId = $manager->findClientIdAndCredentials($request->reveal(), $method, $credentials);
        self::assertInstanceOf(ClientSecretPost::class, $method);
        self::assertInstanceOf(ClientId::class, $clientId);
        self::assertEquals('CLIENT_SECRET', $credentials);
    }
}
