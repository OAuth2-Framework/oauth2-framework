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

use Http\HttplugBundle\HttplugBundle;
use Jose\Bundle\JoseFramework\JoseFrameworkBundle;
use OAuth2Framework\ServerBundle\OAuth2FrameworkServerBundle;
use OAuth2Framework\ServerBundle\Tests\TestBundle\TestBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

/**
 * Class AppKernel.
 */
class AppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, false);
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new SensioFrameworkExtraBundle(),
            new HttplugBundle(),

            new OAuth2FrameworkServerBundle(),
            new TestBundle(),

            new JoseFrameworkBundle(),
        ];

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_test.yml');
    }
}
