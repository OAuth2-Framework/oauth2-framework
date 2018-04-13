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

namespace OAuth2Framework\ServerBundle\Component\Grant\RefreshToken;

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class RefreshTokenSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'refresh_token';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if ($configs['grant']['refresh_token']['enabled']) {
            $container->setParameter('oauth2_server.grant.refresh_token.min_length', $configs['grant']['refresh_token']['min_length']);
            $container->setParameter('oauth2_server.grant.refresh_token.max_length', $configs['grant']['refresh_token']['max_length']);
            $container->setParameter('oauth2_server.grant.refresh_token.lifetime', $configs['grant']['refresh_token']['lifetime']);
            $container->setAlias('oauth2_server.grant.refresh_token.repository', $configs['grant']['refresh_token']['repository']);
            $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
            $loader->load('refresh_token.php');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode('refresh_token')
                ->canBeEnabled()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['repository']);
                    })
                    ->thenInvalid('The option "repository" must be set.')
                ->end()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && $config['max_length'] < $config['min_length'];
                    })
                    ->thenInvalid('The option "max_length" must be greater than "min_length".')
                ->end()
                ->children()
                    ->integerNode('min_length')
                        ->defaultValue(50)
                        ->min(0)
                        ->info('Minimum length of the randomly generated refresh tokens')
                    ->end()
                    ->integerNode('max_length')
                        ->defaultValue(100)
                        ->min(1)
                        ->info('Maximum length of the randomly generated refresh tokens')
                    ->end()
                    ->integerNode('lifetime')
                        ->defaultValue(60 * 60 * 24 * 7)
                        ->min(1)
                        ->info('The refresh token lifetime (in seconds)')
                    ->end()
                    ->scalarNode('repository')
                        ->defaultNull()
                        ->info('The refresh token repository')
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
        //Nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        //Nothing to do
        return [];
    }
}
