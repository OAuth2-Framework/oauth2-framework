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

namespace OAuth2Framework\ServerBundle\Tests\Functional\Core\Client;

use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Client\Command;
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
        if (!interface_exists(ClientRepository::class)) {
            $this->markTestSkipped('The component "oauth-framework/core" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theChangeOwnerCommandHandlerIsAvailable()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        self::assertTrue($container->has(Command\ChangeOwnerCommandHandler::class));
    }

    /**
     * @test
     */
    public function theCreateClientCommandHandlerIsAvailable()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        self::assertTrue($container->has(Command\CreateClientCommandHandler::class));
    }

    /**
     * @test
     */
    public function theUpdateClientCommandHandlerIsAvailable()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        self::assertTrue($container->has(Command\UpdateClientCommandHandler::class));
    }

    /**
     * @test
     */
    public function theDeleteClientCommandHandlerIsAvailable()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        self::assertTrue($container->has(Command\DeleteClientCommandHandler::class));
    }
}
