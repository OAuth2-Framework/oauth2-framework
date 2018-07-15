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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Metadata;

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Metadata\Compiler\CustomRoutesCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomRouteSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'custom_routes';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->setParameter('oauth2_server.endpoint.metadata.custom_routes', $configs['endpoint']['metadata']['custom_routes']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode('custom_routes')
            ->info('Custom routes added to the metadata response.')
            ->useAttributeAsKey('name')
            ->treatNullLike([])
            ->treatFalseLike([])
            ->arrayPrototype()
            ->children()
            ->scalarNode('route_name')
            ->info('Route name.')
            ->isRequired()
            ->end()
            ->arrayNode('route_parameters')
            ->info('Parameters associated to the route (if needed).')
            ->useAttributeAsKey('name')
            ->variablePrototype()->end()
            ->treatNullLike([])
            ->end()
            ->end()
            ->end()
            ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CustomRoutesCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
