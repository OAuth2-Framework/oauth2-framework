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

namespace OAuth2Framework\Bundle\DependencyInjection\Source\OpenIdConnect;

use OAuth2Framework\Bundle\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PairwiseSubjectSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $container->setAlias($path.'.service', $config['service']);
        $container->setParameter($path.'.is_default', $config['is_default']);
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'pairwise_subject';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['service']);
                })
                ->thenInvalid('The pairwise subject service must be set.')
            ->end()
            ->children()
                ->scalarNode('service')->end()
                ->booleanNode('is_default')->defaultTrue()->end()
            ->end();
    }
}
