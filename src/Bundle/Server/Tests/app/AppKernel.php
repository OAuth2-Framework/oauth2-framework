<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Tests\App;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use SpomkyLabs\JoseBundle\SpomkyLabsJoseBundle;
use SimpleBus\SymfonyBridge\SimpleBusEventBusBundle;
use SimpleBus\SymfonyBridge\SimpleBusCommandBusBundle;
use OAuth2Framework\Bundle\Server\Tests\TestBundle\SpomkyLabsTestBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use OAuth2Framework\Bundle\Server\OAuth2FrameworkServerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

final class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),

            new SensioFrameworkExtraBundle(),

            new SpomkyLabsJoseBundle(),
            new OAuth2FrameworkServerBundle(),
            new SpomkyLabsTestBundle(),

            new SimpleBusEventBusBundle(),
            new SimpleBusCommandBusBundle(),
        ];

        return $bundles;
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/OAuth2FrameworkServerTest/'.$this->getEnvironment();
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
