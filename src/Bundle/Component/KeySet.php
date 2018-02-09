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

namespace OAuth2Framework\Bundle\Component;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KeySet implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'key_set';
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('signature')->defaultNull()->end()
                ->scalarNode('encryption')->defaultNull()->end()
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
        if (null !== $config['key_set']['signature']) {
            ConfigurationHelper::addKeyset($container, 'oauth2_server.key_set.signature', 'jwkset', ['value' => $config['key_set']['signature']]);
        }
        if (null !== $config['key_set']['encryption']) {
            ConfigurationHelper::addKeyset($container, 'oauth2_server.key_set.encryption', 'jwkset', ['value' => $config['key_set']['encryption']]);
        }

        return [];
    }
}