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

namespace OAuth2Framework\Bundle\Server\TokenEndpointPlugin;

use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\TokenEndpointPlugin\DependencyInjection\Compiler\RefreshTokenCompilerPass;
use OAuth2Framework\Bundle\Server\TokenEndpointPlugin\DependencyInjection\Compiler\TokenEndpointExtensionCompilerPass;
use OAuth2Framework\Bundle\Server\TokenEndpointPlugin\DependencyInjection\Compiler\TokenRouteCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class TokenEndpointPlugin extends CommonPluginMethod implements BundlePlugin
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'token_endpoint';
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
                ->scalarNode('client_manager')
                    ->info('The client manager.')
                    ->isRequired()
                ->end()
                ->scalarNode('user_account_manager')
                    ->info('The user account manager.')
                    ->isRequired()
                ->end()
                ->scalarNode('path')
                    ->info('The path to the token endpoint')
                    ->defaultValue('/oauth/v2/token')
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
        foreach (['token.endpoint'] as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
        }

        $parameters = [
            'oauth2_server.token_endpoint.access_token_manager' => ['type' => 'alias', 'path' => '[access_token_manager]'],
            'oauth2_server.token_endpoint.client_manager' => ['type' => 'alias', 'path' => '[client_manager]'],
            'oauth2_server.token_endpoint.user_account_manager' => ['type' => 'alias', 'path' => '[user_account_manager]'],
            'oauth2_server.token_endpoint.path' => ['type' => 'parameter', 'path' => '[path]'],
        ];
        if (null !== $pluginConfiguration['refresh_token_manager']) {
            $parameters['oauth2_server.token_endpoint.refresh_token_manager'] = ['type' => 'alias', 'path' => '[refresh_token_manager]'];
        }

        $this->loadParameters($parameters, $pluginConfiguration, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TokenEndpointExtensionCompilerPass());
        $container->addCompilerPass(new RefreshTokenCompilerPass());
        $container->addCompilerPass(new TokenRouteCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container)
    {
    }
}
