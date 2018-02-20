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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class KeySet extends ArraySource
{
    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'key_set';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('signature')->defaultNull()->end()
                ->scalarNode('encryption')->defaultNull()->end()
            ->end();
    }

    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        parent::prepend($bundleConfig, $path, $container);

        if (null !== $bundleConfig['key_set']['signature']) {
            ConfigurationHelper::addKeyset($container, 'oauth2_server.key_set.signature', 'jwkset', ['value' => $bundleConfig['key_set']['signature']]);
        }
        if (null !== $bundleConfig['key_set']['encryption']) {
            ConfigurationHelper::addKeyset($container, 'oauth2_server.key_set.encryption', 'jwkset', ['value' => $bundleConfig['key_set']['encryption']]);
        }
    }
}
