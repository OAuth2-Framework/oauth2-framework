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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Grant;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AuthorizationCodeSource implements Component
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['min_length', 'max_length', 'lifetime', 'enforce_pkce'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
        $container->setAlias($path.'.event_store', $config['event_store']);

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('authorization_code.php');
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'authorization_code';
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['event_store']);
                })
                ->thenInvalid('The option "event_store" must be set.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && $config['max_length'] < $config['min_length'];
                })
                ->thenInvalid('The option "max_length" must be greater than "min_length".')
            ->end()
            ->children()
                ->integerNode('min_length')->defaultValue(50)->min(0)->end()
                ->integerNode('max_length')->defaultValue(100)->min(1)->end()
                ->integerNode('lifetime')->defaultValue(30)->min(1)->end()
                ->scalarNode('event_store')->defaultNull()->end()
                ->booleanNode('enforce_pkce')->defaultFalse()->end()
            ->end();
    }
}
