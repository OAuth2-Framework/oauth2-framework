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

namespace OAuth2Framework\ServerBundle\Tests\Functional\Core\AccessToken;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\AccessToken\Command;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Core
 */
class CommandTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!interface_exists(AccessTokenRepository::class)) {
            $this->markTestSkipped('The component "oauth-framework/core" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theCreateAccessTokenCommandHandlerIsAvailable()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        self::assertTrue($container->has(Command\CreateAccessTokenCommandHandler::class));
    }

    /**
     * @test
     * @depends theCreateAccessTokenCommandHandlerIsAvailable
     */
    public function iCanCreateAnAccessTokenUsingTheCommandHandler()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        /** @var Command\CreateAccessTokenCommandHandler $handler */
        $handler = $container->get(Command\CreateAccessTokenCommandHandler::class);

        $command = Command\CreateAccessTokenCommand::create(
            AccessTokenId::create('ACCESS_TOKEN_CREATED_USING_A_COMMAND'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            new \DateTimeImmutable('now +1 hour'),
            DataBag::create([]),
            DataBag::create([]),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $handler->handle($command);

        /** @var AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $container->get('MyAccessTokenRepository');

        $accessToken = $accessTokenRepository->find(AccessTokenId::create('ACCESS_TOKEN_CREATED_USING_A_COMMAND'));

        self::assertInstanceOf(AccessToken::class, $accessToken);
    }

    /**
     * @test
     */
    public function theRevokeAccessTokenCommandHandlerIsAvailable()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        self::assertTrue($container->has(Command\RevokeAccessTokenCommandHandler::class));
    }
}
