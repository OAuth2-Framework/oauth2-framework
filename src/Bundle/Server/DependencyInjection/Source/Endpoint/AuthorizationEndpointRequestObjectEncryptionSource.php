<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Endpoint;

use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AuthorizationEndpointRequestObjectEncryptionSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['required', 'key_encryption_algorithms', 'content_encryption_algorithms'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
        $container->setAlias($path.'.key_set', $config['key_set']);
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'encryption';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->children()
                ->scalarNode('key_set')
                    ->info('Key set for the encryption support.')
                ->end()
                ->booleanNode('required')
                    ->info('If true, incoming request objects must be encrypted.')
                    ->defaultFalse()
                ->end()
                ->arrayNode('key_encryption_algorithms')
                    ->info('Supported key encryption algorithms.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
                ->arrayNode('content_encryption_algorithms')
                    ->info('Supported content encryption algorithms.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
            ->end();
    }
}
