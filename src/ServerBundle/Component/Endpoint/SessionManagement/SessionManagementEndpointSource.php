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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\SessionManagement;

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        $config = $configs['endpoint']['session_management'];
        $container->setParameter('oauth2_server.endpoint.session_management.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.session_management.path', $config['path']);
        $container->setParameter('oauth2_server.endpoint.session_management.host', $config['host']);
        $container->setParameter('oauth2_server.endpoint.session_management.storage_name', $config['storage_name']);
        $container->setParameter('oauth2_server.endpoint.session_management.template', $config['template']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/session_management'));
        //$loader->load('session_management.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
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
                        ->info('The session management endpoint')
                        ->defaultValue('/session')
                    ->end()
                    ->scalarNode('host')
                        ->info('If set, the route will be limited to that host')
                        ->defaultValue('')
                        ->treatNullLike('')
                        ->treatFalseLike('')
                    ->end()
                    ->scalarNode('storage_name')
                        ->info('The name used for the cookie storage')
                        ->defaultValue('oidc_browser_state')
                    ->end()
                    ->scalarNode('template')
                        ->info('The template of the OP iframe.')
                        ->defaultValue('@OAuth2FrameworkServerBundle/iframe/iframe.html.twig')
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
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
