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

namespace OAuth2Framework\Bundle\DependencyInjection\Component;

use Fluent\PhpConfigFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FirewallSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'firewall';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config'));
        $loader->load('security.php');
    }
}
