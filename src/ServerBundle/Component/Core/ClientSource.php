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

use OAuth2Framework\Component\Core\Client\ClientIdGenerator;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Service\RandomClientIdGenerator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'client';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->setAlias(ClientRepository::class, $configs['client']['repository']);
        $container->setAlias(ClientIdGenerator::class, $configs['client']['id_generator']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/core'));
        $loader->load('client.php');
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
            ->info('The client repository service')
            ->isRequired()
            ->end()
            ->scalarNode('id_generator')
            ->info('The client ID generator service')
            ->defaultValue(RandomClientIdGenerator::class)
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
