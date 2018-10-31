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

namespace OAuth2Framework\ServerBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Http\HttplugBundle\HttplugBundle;
use Jose\Bundle\JoseFramework\JoseFrameworkBundle;
use OAuth2Framework\ServerBundle\OAuth2FrameworkServerBundle;
use OAuth2Framework\ServerBundle\Tests\TestBundle\TestBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

final class AppKernel extends Kernel
{
    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, false);
    }

    public function registerBundles()
    {
        $bundles = [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new SensioFrameworkExtraBundle(),
            new HttplugBundle(),
            new DoctrineBundle(),
            new DoctrineFixturesBundle(),

            new OAuth2FrameworkServerBundle(),
            new TestBundle(),

            new JoseFrameworkBundle(),
        ];

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_test.yml');
    }
}
