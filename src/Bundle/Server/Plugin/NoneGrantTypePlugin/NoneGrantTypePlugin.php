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

namespace OAuth2Framework\Bundle\Server\NoneGrantTypePlugin;

use Assert\Assertion;
use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\NoneGrantTypePlugin\DependencyInjection\Compiler\NoneResponseTypeListenerCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class NoneGrantTypePlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'none_response_type';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('access_token_manager')
                    ->info('The access token manager.')
                    ->isRequired()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));

        $parameters = [
            'oauth2_server.none_response_type.access_token_manager' => ['type' => 'alias', 'path' => '[access_token_manager]'],
        ];

        $this->loadParameters($parameters, $pluginConfiguration, $container);

        $loader->load('none_response_type.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new NoneResponseTypeListenerCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $config = current($container->getExtensionConfig('oauth2_server'));
        Assertion::keyExists($config, 'authorization_endpoint', 'The "AuthorizationEndpointPlugin" must be enabled to use the "NoneGrantTypePlugin".');

        if (array_key_exists('token_endpoint', $config)) {
            foreach (['access_token_manager'] as $name) {
                $config[$this->name()][$name] = $config['token_endpoint'][$name];
            }
        } elseif (array_key_exists('implicit_grant_type', $config)) {
            foreach (['access_token_manager'] as $name) {
                $config[$this->name()][$name] = $config['implicit_grant_type'][$name];
            }
        }

        $container->prependExtensionConfig('oauth2_server', $config);
    }
}
