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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Endpoint;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MetadataEndpointSource extends ActionableSource
{
    /**
     * MetadataEndpointSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new SignedMetadataEndpointSource());
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['path', 'custom_values', 'custom_routes'] as $key) {
            $container->setParameter($path.'.'.$key, $config[$key]);
        }
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint'));
        $loader->load('metadata.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'metadata';
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
                    return true === $config['enabled'] && empty($config['path']);
                })
                ->thenInvalid('The route name must be set.')
            ->end()
            ->children()
                ->scalarNode('path')->defaultValue('/.well-known/openid-configuration')->end()
                ->arrayNode('custom_routes')
                    ->info('Custom routes added to the metadata response.')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('route_name')
                                ->info('Route name.')
                                ->isRequired()
                            ->end()
                            ->arrayNode('route_parameters')
                                ->info('Parameters associated to the route (if needed).')
                                ->useAttributeAsKey('name')
                                ->prototype('variable')->end()
                                ->treatNullLike([])
                            ->end()
                        ->end()
                    ->end()
                    ->treatNullLike([])
                ->end()
                ->arrayNode('custom_values')
                    ->info('Custom values added to the metadata response.')
                    ->useAttributeAsKey('name')
                    ->prototype('variable')->end()
                    ->treatNullLike([])
                ->end()
            ->end();
    }
}
