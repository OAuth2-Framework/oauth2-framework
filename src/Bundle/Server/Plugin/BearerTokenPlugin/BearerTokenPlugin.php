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

namespace OAuth2Framework\Bundle\Server\BearerTokenPlugin;

use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\BearerTokenPlugin\DependencyInjection\Compiler\BearerTokenConfigurationCompilerPass;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class BearerTokenPlugin extends CommonPluginMethod implements BundlePlugin
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'bearer_token';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $this->initConfigurationParametersAndAliases($pluginConfiguration, $container);
        $this->loadFiles($container);
    }

    /**
     * @param array                                                   $pluginConfiguration
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    private function initConfigurationParametersAndAliases(array $pluginConfiguration, ContainerBuilder $container)
    {
        $parameters = [
            'oauth2_server.bearer_token.authorization_header' => ['type' => 'parameter', 'path' => '[authorization_header]'],
            'oauth2_server.bearer_token.query_string' => ['type' => 'parameter', 'path' => '[query_string]'],
            'oauth2_server.bearer_token.request_body' => ['type' => 'parameter', 'path' => '[request_body]'],
            'oauth2_server.bearer_token.realm' => ['type' => 'parameter', 'path' => '[realm]'],
        ];

        $this->loadParameters($parameters, $pluginConfiguration, $container);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    private function loadFiles(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $files = [
            'services',
        ];

        foreach ($files as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('authorization_header')
                    ->info('Allow access token to be passed in the authorization header. This method is recommended')
                    ->defaultTrue()
                ->end()
                ->booleanNode('query_string')
                    ->info('Allow access token to be passed in the query string parameter "access_token". This method is not recommended')
                    ->defaultFalse()
                ->end()
                ->booleanNode('request_body')
                    ->info('Allow access token to be passed in the request body parameter "access_token". This method is not recommended.')
                    ->defaultFalse()
                ->end()
                ->scalarNode('realm')
                    ->info('Realm.')
                    ->defaultNull()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new BearerTokenConfigurationCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container)
    {
    }
}
