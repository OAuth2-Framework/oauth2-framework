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

namespace OAuth2Framework\ServerBundle\Component\Core;

use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuerRepository;
use OAuth2Framework\ServerBundle\Component\Component;
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
        if (!interface_exists(TrustedIssuerRepository::class)) {
            return;
        }
        if (null === $configs['trusted_issuer']['repository']) {
            return;
        }
        $container->setAlias(TrustedIssuerRepository::class, $configs['trusted_issuer']['repository']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        if (!interface_exists(TrustedIssuerRepository::class)) {
            return;
        }
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
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
