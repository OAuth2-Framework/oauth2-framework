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

namespace OAuth2Framework\Bundle\Component\Endpoint\IssuerDiscovery;

use OAuth2Framework\Bundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class IssuerDiscoveryEndpointSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'issuer_discovery';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        //$container->setParameter('oauth2_server.endpoint.issuer_discovery.path', $configs['endpoint']['issuer_discovery']['path']);

        //$loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/issuer_discovery'));
        //$loader->load('issuer_discovery.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode($this->name())
                ->defaultValue([])
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('path')
                            ->isRequired()
                        ->end()
                        ->scalarNode('resource_repository')
                            ->isRequired()
                        ->end()
                        ->scalarNode('server')
                            ->isRequired()
                        ->end()
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
        return [];
    }
}
