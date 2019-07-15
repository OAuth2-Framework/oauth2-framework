<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
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
    public function name(): string
    {
        return 'trusted_issuer';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (!interface_exists(TrustedIssuerRepository::class)) {
            return;
        }
        if (null === $configs['trusted_issuer']['repository']) {
            return;
        }
        $container->setAlias(TrustedIssuerRepository::class, $configs['trusted_issuer']['repository']);
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
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
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
