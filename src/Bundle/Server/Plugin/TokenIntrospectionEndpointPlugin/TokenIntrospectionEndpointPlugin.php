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

namespace OAuth2Framework\Bundle\Server\TokenIntrospectionEndpointPlugin;

use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\TokenIntrospectionEndpointPlugin\DependencyInjection\Compiler\IntrospectionTokenTypeCompilerPass;
use OAuth2Framework\Bundle\Server\TokenIntrospectionEndpointPlugin\DependencyInjection\Compiler\TokenIntrospectionRouteCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class TokenIntrospectionEndpointPlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'token_introspection_endpoint';
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new IntrospectionTokenTypeCompilerPass());
        $container->addCompilerPass(new TokenIntrospectionRouteCompilerPass());
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
                ->scalarNode('refresh_token_manager')
                    ->info('The refresh token manager.')
                    ->defaultNull()
                ->end()
                ->scalarNode('path')
                    ->info('The path to the token introspection endpoint')
                    ->defaultValue('/oauth/v2/introspect')
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
        $files = ['introspection.endpoint', 'access_token_introspection'];

        $parameters = [
            'oauth2_server.token_introspection_endpoint.path' => ['type' => 'parameter', 'path' => '[path]'],
            'oauth2_server.token_introspection_endpoint.access_token_manager' => ['type' => 'alias', 'path' => '[access_token_manager]'],
        ];
        if (null !== $pluginConfiguration['refresh_token_manager']) {
            $files[] = 'refresh_token_introspection';
            $parameters['oauth2_server.token_introspection_endpoint.refresh_token_manager'] = ['type' => 'alias', 'path' => '[refresh_token_manager]'];
        }

        foreach ($files as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
        }

        $this->loadParameters($parameters, $pluginConfiguration, $container);
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
        if (array_key_exists('token_endpoint', $config)) {
            foreach (['refresh_token_manager', 'access_token_manager'] as $name) {
                $config[$this->name()][$name] = $config['token_endpoint'][$name];
            }
        }
        $container->prependExtensionConfig('oauth2_server', $config);
    }
}
