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

namespace OAuth2Framework\ServerBundle\Component\Scope;

use OAuth2Framework\Component\Scope\ScopeRepository;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Scope\Compiler\ScopeMetadataCompilerPass;
use OAuth2Framework\ServerBundle\Component\Scope\Compiler\ScopePolicyCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        if (!interface_exists(ScopeRepository::class) || !$configs['scope']['enabled']) {
            return;
        }

        $container->setAlias(ScopeRepository::class, $configs['scope']['repository']);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/scope'));
        $loader->load('scope.php');

        if (!$configs['scope']['policy']['enabled']) {
            $container->setParameter('oauth2_server.scope.policy.by_default', 'none');

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
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        if (!interface_exists(ScopeRepository::class)) {
            return;
        }
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
        $container->addCompilerPass(new ScopePolicyCompilerPass());
        $container->addCompilerPass(new ScopeMetadataCompilerPass());
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
