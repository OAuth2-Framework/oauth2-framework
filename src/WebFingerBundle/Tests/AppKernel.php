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

namespace OAuth2Framework\WebFingerBundle\Tests;

use OAuth2Framework\WebFingerBundle\OAuth2FrameworkWebFingerBundle;
use OAuth2Framework\WebFingerBundle\Tests\TestBundle\TestBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class AppKernel.
 */
class AppKernel extends Kernel
{
    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, false);
    }

    public function registerBundles()
    {
        $bundles = [
            new FrameworkBundle(),
            new OAuth2FrameworkWebFingerBundle(),
            new SensioFrameworkExtraBundle(),
            new TestBundle(),
        ];

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_test.yml');
    }
}
