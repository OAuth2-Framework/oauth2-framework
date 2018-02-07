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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Endpoint\SessionManagement;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SessionManagementEndpointSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'session_management';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['endpoint']['session_management']['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.session_management.path', $configs['endpoint']['session_management']['path']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../../Resources/config/endpoint/session_management'));
        //$loader->load('session_management.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['path']);
                    })
                    ->thenInvalid('The route name must be set.')
                ->end()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['storage_name']);
                    })->thenInvalid('The option "storage_name" must be set.')
                ->end()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['template']);
                    })->thenInvalid('The option "template" must be set.')
                ->end()
                ->children()
                    ->scalarNode('path')
                    ->end()
                    ->scalarNode('storage_name')
                        ->defaultValue('oidc_browser_state')
                    ->end()
                    ->scalarNode('template')
                        ->info('The template of the OP iframe.')
                        ->defaultValue('@OAuth2FrameworkBundle/iframe/iframe.html.twig')
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
