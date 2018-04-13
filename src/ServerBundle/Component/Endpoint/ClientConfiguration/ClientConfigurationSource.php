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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\ClientConfiguration;

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\ClientConfiguration\Compiler\ClientConfigurationEndpointRouteCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        $config = $configs['endpoint']['client_configuration'];
        $container->setParameter('oauth2_server.endpoint.client_configuration.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.client_configuration.path', $config['path']);
        $container->setParameter('oauth2_server.endpoint.client_configuration.host', $config['host']);
        $container->setParameter('oauth2_server.endpoint.client_configuration.realm', $config['realm']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/client_configuration'));
        $loader->load('client_configuration.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['realm']);
                    })
                    ->thenInvalid('The option "realm" must be set.')
                ->end()
                ->canBeEnabled()
                ->children()
                    ->scalarNode('realm')
                    ->end()
                    ->scalarNode('path')
                        ->defaultValue('/client/configure/{client_id}')
                    ->end()
                    ->scalarNode('host')
                        ->info('If set, the route will be limited to that host')
                        ->defaultValue('')
                        ->treatFalseLike('')
                        ->treatNullLike('')
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ClientConfigurationEndpointRouteCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
