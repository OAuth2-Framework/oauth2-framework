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

namespace OAuth2Framework\ServerBundle\Tests\Functional;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseTestCase extends WebTestCase
{
    /**
     * @var Application
     */
    protected static $application;

    /**
     * @var ManagerRegistry
     */
    protected static $registryManager;

    protected function setUp()
    {
        self::bootKernel();
        self::$application = new Application(static::$kernel);
        self::$application->setAutoExit(false);

        self::runCommand('doctrine:database:drop --force');
        self::runCommand('doctrine:database:create');
        self::runCommand('doctrine:schema:create');
        self::runCommand('doctrine:fixtures:load --append --no-interaction');

        self::$registryManager = self::$kernel->getContainer()->get('doctrine');
    }

    protected static function runCommand(string $command, ?OutputInterface $output = null): void
    {
        $command = sprintf('%s --quiet', $command);

        self::$application->run(new StringInput($command), $output);
    }

    protected function tearDown()
    {
        //self::runCommand('doctrine:database:drop --force');
        parent::tearDown();
    }
}
