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

namespace OAuth2Framework\Bundle\Server\TokenTypeManagerPlugin;

use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\TokenTypeManagerPlugin\DependencyInjection\Compiler\TokenTypeCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class TokenTypeManagerPlugin extends CommonPluginMethod implements BundlePlugin
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'token_type_manager';
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
            'oauth2_server.token_type_manager.default' => ['type' => 'parameter', 'path' => '[default]'],
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
                ->scalarNode('default')
                    ->defaultNull()
                    ->info('The default token type used by this server. Not needed if you only have one access token type (e.g. "Bearer", "MAC", "POP"). If not set, the first token type will be the default one.')
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TokenTypeCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container)
    {
    }
}
