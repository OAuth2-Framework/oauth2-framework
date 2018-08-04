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
use OAuth2Framework\ServerBundle\Component\Endpoint\Metadata\Compiler\CustomValuesCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomValuesSource implements Component
{
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
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode('custom_values')
            ->info('Custom values added to the metadata response.')
            ->useAttributeAsKey('name')
            ->variablePrototype()->end()
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
