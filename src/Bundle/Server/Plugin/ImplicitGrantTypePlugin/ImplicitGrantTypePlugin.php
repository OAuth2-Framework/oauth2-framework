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

namespace OAuth2Framework\Bundle\Server\ImplicitGrantTypePlugin;

use Assert\Assertion;
use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\ImplicitGrantTypePlugin\DependencyInjection\Compiler\ConfigurationCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ImplicitGrantTypePlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'implicit_grant_type';
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
            'oauth2_server.implicit_grant_type.allow_confidential_clients' => ['type' => 'parameter', 'path' => '[allow_confidential_clients]'],
            'oauth2_server.implicit_grant_type.access_token_manager' => ['type' => 'alias', 'path' => '[access_token_manager]'],
        ];

        $this->loadParameters($parameters, $pluginConfiguration, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->children()
                ->scalarNode('access_token_manager')
                    ->info('The access token manager')
                    ->isRequired()
                ->end()
                ->booleanNode('allow_confidential_clients')
                    ->info('If true, confidential clients are allowed to issue access tokens using this grant type. Default is false. It is not recommended to set true unless you want to use OpenID Connect hybrid flows.')
                    ->defaultFalse()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ConfigurationCompilerPass());
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
        Assertion::keyExists($config, 'authorization_endpoint', 'The "AuthorizationEndpointPlugin" must be enabled to use the "ImplicitGrantTypePlugin".');

        $config = current($container->getExtensionConfig('oauth2_server'));
        if (array_key_exists('token_endpoint', $config)) {
            foreach (['access_token_manager'] as $name) {
                $config[$this->name()][$name] = $config['token_endpoint'][$name];
            }
        }
        $container->prependExtensionConfig('oauth2_server', $config);
    }
}
