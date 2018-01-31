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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\TokenType;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MacTokenTypeSource implements Component
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($config as $k => $v) {
            $container->setParameter($path.'.'.$k, $v);
        }

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/token_type'));
        $loader->load('mac_token.php');
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'mac_token';
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return $config['min_length'] > $config['max_length'];
                })
                ->thenInvalid('The option "min_length" must not be greater than "max_length".')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return !in_array($config['algorithm'], ['hmac-sha-256', 'hmac-sha-1']);
                })
                ->thenInvalid('The algorithm is not supported. Please use one of the following one: "hmac-sha-1", "hmac-sha-256".')
            ->end()
            ->children()
                ->integerNode('min_length')->defaultValue(50)->min(1)->end()
                ->integerNode('max_length')->defaultValue(100)->min(2)->end()
                ->scalarNode('algorithm')->defaultValue('hmac-sha-256')->end()
                ->integerNode('timestamp_lifetime')->defaultValue(10)->min(1)->end()
            ->end();
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
