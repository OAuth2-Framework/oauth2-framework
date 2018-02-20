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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ServerNameSource implements SourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(string $path, ContainerBuilder $container, array $config)
    {
        $container->setParameter($path.'.server_uri', $config['server_uri']);
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('server_uri')
                ->info('The Uri of this server.')
                ->isRequired()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        // Nothing to do
    }
}
