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

namespace OAuth2Framework\Bundle\Component\Endpoint\Metadata;

use OAuth2Framework\Bundle\Component\Component;
use OAuth2Framework\Bundle\Component\Endpoint\Metadata\Compiler\CustomValuesCompilerPass;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomValuesSource implements Component
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
        $container->setParameter('oauth2_server.endpoint.metadata.custom_values', $configs['endpoint']['metadata']['custom_values']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode('custom_values')
                ->info('Custom values added to the metadata response.')
                ->useAttributeAsKey('name')
                ->prototype('variable')->end()
                ->treatNullLike([])
                ->treatFalseLike([])
            ->end()
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CustomValuesCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
