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

final class ResourceServerRepositorySource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'resource_server';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['repository'] as $k) {
            $container->setAlias($path.'.'.$k, $config[$k]);
        }
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
                    return true === $config['enabled'] && empty($config['repository']);
                })
                ->thenInvalid('The option "repository" must be set.')
            ->end()
            ->children()
                ->scalarNode('repository')
                    ->info('The resource server repository.')
                    ->isRequired()
                ->end()
            ->end();
    }
}
