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

namespace OAuth2Framework\Bundle\DependencyInjection\Source\Grant;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ImplicitSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('implicit.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'implicit';
    }
}