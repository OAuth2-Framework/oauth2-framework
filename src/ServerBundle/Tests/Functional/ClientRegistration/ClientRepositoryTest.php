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

namespace OAuth2Framework\ServerBundle\Tests\Functional\ClientRegistration;

use OAuth2Framework\ServerBundle\Tests\Functional\DatabaseTestCase;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\Client;

/**
 * @group ServerBundle
 * @group Functional
 * @group ClientRepository
 */
class ClientRepositoryTest extends DatabaseTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private static $entityManager;

    protected function setUp()
    {
        parent::setUp();
        self::$entityManager = self::$registryManager->getManagerForClass(Client::class);
    }

    /**
     * @test
     */
    public function addClientToDatabase()
    {
        $clients = self::$entityManager
            ->getRepository(Client::class)
            ->findAll()
        ;

        static::assertCount(5, $clients);
    }

    protected function tearDown()
    {
        self::$entityManager->close();
        self::$entityManager;
        parent::tearDown();
    }
}
