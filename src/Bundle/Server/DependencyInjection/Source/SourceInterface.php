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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface SourceInterface
{
    /**
     * @param string           $path
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function load(string $path, ContainerBuilder $container, array $config);

    /**
     * @param NodeDefinition $node
     */
    public function addConfiguration(NodeDefinition $node);

    /**
     * @param array             $bundleConfig
     * @param string            $path
     * @param ContainerBuilder $container
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container);
}
