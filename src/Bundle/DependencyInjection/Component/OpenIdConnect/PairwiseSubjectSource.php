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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\OpenIdConnect;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PairwiseSubjectSource implements Component
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
    public function name(): string
    {
        return 'pairwise_subject';
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
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
