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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Endpoint\ClientConfiguration;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientConfigurationSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'client_configuration';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['endpoint']['client_configuration']['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.client_configuration.path', $configs['endpoint']['client_configuration']['path']);
        $container->setParameter('oauth2_server.endpoint.client_configuration.realm', $configs['endpoint']['client_configuration']['realm']);
        $container->setParameter('oauth2_server.endpoint.client_configuration.authorization_header', $configs['endpoint']['client_configuration']['authorization_header']);
        $container->setParameter('oauth2_server.endpoint.client_configuration.query_string', $configs['endpoint']['client_configuration']['query_string']);
        $container->setParameter('oauth2_server.endpoint.client_configuration.request_body', $configs['endpoint']['client_configuration']['request_body']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../../Resources/config/endpoint/client_configuration'));
        $loader->load('client_configuration.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode($this->name())
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['realm']);
                    })
                    ->thenInvalid('The option "realm" must be set.')
                ->end()
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
                ->children()
                    ->scalarNode('realm')
                        ->end()
                    ->booleanNode('authorization_header')
                        ->defaultTrue()
                    ->end()
                    ->booleanNode('query_string')
                        ->defaultFalse()
                    ->end()
                    ->booleanNode('request_body')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode('path')
                        ->defaultValue('/client/configure/{client_id}')
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
