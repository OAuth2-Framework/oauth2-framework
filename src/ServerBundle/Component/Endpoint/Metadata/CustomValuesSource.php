<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
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

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->setParameter('oauth2_server.endpoint.metadata.custom_values', $configs['endpoint']['metadata']['custom_values']);
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
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

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CustomValuesCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
