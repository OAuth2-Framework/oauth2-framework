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

namespace OAuth2Framework\Bundle\Server\ScopeManagerPlugin;

use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\ScopeManagerPlugin\DependencyInjection\Compiler\AuthorizationCodeGrantTypeCompilerPass;
use OAuth2Framework\Bundle\Server\ScopeManagerPlugin\DependencyInjection\Compiler\MetadataCompilerPass;
use OAuth2Framework\Bundle\Server\ScopeManagerPlugin\DependencyInjection\Compiler\ScopePolicyCompilerPass;
use OAuth2Framework\Bundle\Server\ScopeManagerPlugin\DependencyInjection\Compiler\TokenEndpointCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ScopeManagerPlugin extends CommonPluginMethod implements BundlePlugin
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'scope';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        foreach (['services'] as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
        }

        $parameters = [
            'oauth2_server.scope_manager.scope_policy' => ['type' => 'parameter', 'path' => '[policy]'],
            'oauth2_server.scope.available_scope'      => ['type' => 'parameter', 'path' => '[available_scope]', 'callback' => function ($value) {
                return array_unique($value);
            }],
        ];

        $this->loadParameters($parameters, $pluginConfiguration, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('policy')
                    ->defaultValue('none')
                    ->info('Policy applied if no scope is requested by the client (default "none").')
                ->end()
                ->arrayNode('available_scope')
                    ->useAttributeAsKey('name')
                    ->treatNullLike([])
                    ->prototype('scalar')->end()
                    ->info('A list of scopes supported by this server (optional).')
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ScopePolicyCompilerPass());
        $container->addCompilerPass(new MetadataCompilerPass());
        $container->addCompilerPass(new AuthorizationCodeGrantTypeCompilerPass());
        $container->addCompilerPass(new TokenEndpointCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container)
    {
    }
}
