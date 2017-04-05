<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\MacTokenPlugin;

use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\MacTokenPlugin\DependencyInjection\Compiler\MacTokenConfigurationCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MacTokenPlugin extends CommonPluginMethod implements BundlePlugin
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'mac_token';
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
            'oauth2_server.mac_token.min_length' => ['type' => 'parameter', 'path' => '[min_length]'],
            'oauth2_server.mac_token.max_length' => ['type' => 'parameter', 'path' => '[max_length]'],
            'oauth2_server.mac_token.algorithm' => ['type' => 'parameter', 'path' => '[algorithm]'],
            'oauth2_server.mac_token.timestamp_lifetime' => ['type' => 'parameter', 'path' => '[timestamp_lifetime]'],
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
                ->integerNode('min_length')
                    ->info('The minimum length of the MAC key generated during the access token issuance.')
                    ->min(1)
                    ->defaultValue(20)
                ->end()
                ->integerNode('max_length')
                    ->info('The maximum length of the MAC key generated during the access token issuance.')
                    ->min(2)
                    ->defaultValue(30)
                ->end()
                ->scalarNode('algorithm')
                    ->info('The algorithm used by the client to compute the authorization header.')
                    ->defaultValue('hmac-sha-256')
                ->end()
                ->integerNode('timestamp_lifetime')
                    ->info('The lifetime of the timestamp used to compute the authorization header. This value should be as low as possible to prevent replay attacks.')
                    ->min(1)
                    ->defaultValue(10)
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new MacTokenConfigurationCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container)
    {
    }
}
