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

namespace OAuth2Framework\Bundle\Component\Core;

use OAuth2Framework\Bundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TrustedIssuerSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'trusted_issuer';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (null !== $configs['trusted_issuer']['repository']) {
            $container->setAlias('oauth2_server.trusted_issuer_repository', $configs['trusted_issuer']['repository']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('repository')
                        ->info('If set, trusted issuer support will be enabled')
                        ->defaultNull()
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
        //Nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        //Nothing to do
        return [];
    }
}