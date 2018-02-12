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

namespace OAuth2Framework\Bundle\Tests\Functional\ClientRegistration;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationEndpoint;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group Bundle
 * @group Functional
 * @group Grant
 * @group ClientRegistration
 */
class ClientRegistrationEndpointTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!class_exists(TokenRevocationEndpoint::class)) {
            $this->markTestSkipped('The component "oauth2-framework/client-registration-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theClientIsNotAuthenticated()
    {
        $client = static::createClient();
        $client->request('POST', '/client/management', [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(201, $response->getStatusCode());
        dump($response->headers->all());
        $content = json_decode($response->getContent(), true);

        $container = $client->getContainer();
        dump($container->get('MyClientRepository')->find(ClientId::create($content['client_id'])));
        self::assertEquals('', $response->getContent());
    }
}
