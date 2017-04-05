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

namespace OAuth2Framework\Bundle\Server\ResourceOwnerPasswordCredentialsGrantTypePlugin;

use Assert\Assertion;
use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ResourceOwnerPasswordCredentialsGrantTypePlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'resource_owner_password_credentials_grant_type';
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->isRequired()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('user_account_manager')
                    ->info('The user account manager.')
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
        foreach (['services'] as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
        }

        $parameters = [
            'oauth2_server.resource_owner_password_credentials_grant_type.user_account_manager' => ['type' => 'alias', 'path' => '[user_account_manager]'],
        ];

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
        Assertion::keyExists($config, 'token_endpoint', 'The "TokenEndpointPlugin" must be enabled to use the "ResourceOwnerPasswordCredentialsGrantTypePlugin".');

        if (array_key_exists('token_endpoint', $config)) {
            $config[$this->name()]['user_account_manager'] = $config['token_endpoint']['user_account_manager'];
        }
        $container->prependExtensionConfig('oauth2_server', $config);
    }
}
