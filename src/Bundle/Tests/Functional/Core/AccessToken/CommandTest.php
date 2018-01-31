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

namespace OAuth2Framework\Bundle\Tests\Functional\Core\AccessToken;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\AccessToken\Command;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group Bundle
 * @group Functional
 * @group Core
 */
final class CommandTest extends WebTestCase
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
     */
    public function theRevokeAccessTokenCommandHandlerIsAvailable()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        self::assertTrue($container->has(Command\RevokeAccessTokenCommandHandler::class));
    }
}
