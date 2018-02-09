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

namespace OAuth2Framework\Bundle\Component\Scope;

use OAuth2Framework\Bundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ScopeSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'scope';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['scope']['enabled']) {
            return;
        }
        $container->setAlias('oauth2_server.scope.repository', $configs['scope']['repository']);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/scope'));
        $loader->load('scope.php');

        if (!$configs['scope']['policy']['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.scope.policy.by_default', $configs['scope']['policy']['by_default']);
        $loader->load('policy.php');

        if ($configs['scope']['policy']['default']['enabled']) {
            $container->setParameter('oauth2_server.scope.policy.default.scope', $configs['scope']['policy']['default']['scope']);
            $loader->load('policy_default.php');
        }
        if ($configs['scope']['policy']['error']['enabled']) {
            $loader->load('policy_error.php');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->children()
                    ->scalarNode('repository')
                        ->info('Scope repository.')
                        ->defaultNull()
                    ->end()
                    ->arrayNode('policy')
                        ->canBeEnabled()
                        ->children()
                            ->scalarNode('by_default')
                                ->info('Default scope policy.')
                                ->defaultValue('none')
                            ->end()
                            ->arrayNode('error')
                                ->canBeEnabled()
                                ->info('When the error policy is used, requests without a scope are not allowed')
                            ->end()
                            ->arrayNode('default')
                                ->canBeEnabled()
                                ->children()
                                    ->scalarNode('scope')
                                        ->info('Scope added by default.')
                                        ->isRequired()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
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