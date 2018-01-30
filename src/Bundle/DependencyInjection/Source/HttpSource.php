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

namespace OAuth2Framework\Bundle\DependencyInjection\Source;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class HttpSource extends ArraySource
{
    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'http';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($config as $k => $v) {
            $container->setAlias($path.'.'.$k, $v);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node->isRequired();
        $node
            ->children()
                ->scalarNode('client')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('uri_factory')->isRequired()->cannotBeEmpty()->end()
            ->end();
    }
}
