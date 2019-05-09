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

namespace OAuth2Framework\ServerBundle\Component;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KeySet implements Component
{
    public function name(): string
    {
        return 'key_set';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        // TODO: Implement load() method.
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('signature')->defaultNull()->end()
            ->scalarNode('encryption')->defaultNull()->end()
            ->end();
    }

    public function build(ContainerBuilder $container): void
    {
        //Nothing to do
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        if (null !== $config['key_set']['signature']) {
            ConfigurationHelper::addKeyset($container, 'oauth2_server.key_set.signature', 'jwkset', ['value' => $config['key_set']['signature']]);
        }
        if (null !== $config['key_set']['encryption']) {
            ConfigurationHelper::addKeyset($container, 'oauth2_server.key_set.encryption', 'jwkset', ['value' => $config['key_set']['encryption']]);
        }

        return [];
    }
}
