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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Endpoint;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SessionManagementEndpointSource implements Component
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['path', 'storage_name', 'template'] as $key) {
            $container->setParameter($path.'.'.$key, $config[$key]);
        }
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint'));
        $loader->load('session_management.php');
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'session_management';
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node
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
            ->end();
    }
}
