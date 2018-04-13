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

namespace OAuth2Framework\ServerBundle\Component;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface Component
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container);

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container);

    /**
     * @param NodeDefinition $node
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode);

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return array
     */
    public function prepend(ContainerBuilder $container, array $config): array;
}
