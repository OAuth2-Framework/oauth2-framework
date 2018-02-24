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

namespace OAuth2Framework\ServerBundle\Component\Core;

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class AccessTokenSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->setAlias('oauth2_server.access_token_repository', $configs['access_token']['repository']);
        $container->setAlias('oauth2_server.access_token_id_generator', $configs['access_token']['id_generator']);
        $container->setParameter('oauth2_server.access_token_lifetime', $configs['access_token']['lifetime']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/core'));
        $loader->load('access_token.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('repository')
                        ->info('The access token repository service')
                        ->isRequired()
                    ->end()
                    ->scalarNode('id_generator')
                        ->info('The access token ID generator service')
                        ->isRequired()
                    ->end()
                    ->scalarNode('lifetime')
                        ->info('The access token lifetime (in seconds)')
                        ->defaultValue(1800)
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
