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
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        $container->setParameter('oauth2_server.endpoint.issuer_discovery', $configs['endpoint']['issuer_discovery']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/issuer_discovery'));
        $loader->load('issuer_discovery.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->treatNullLike([])
                ->treatFalseLike([])
                ->useAttributeAsKey('name')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('host')
                            ->info('If set, the route will be limited to that host')
                            ->defaultValue('')
                            ->treatFalseLike('')
                            ->treatNullLike('')
                        ->end()
                        ->scalarNode('path')
                            ->info('The path to the issuer discovery endpoint')
                            ->isRequired()
                        ->end()
                        ->scalarNode('resource_repository')
                            ->info('The resource repository service associated to the issuer discovery')
                            ->isRequired()
                        ->end()
                        ->scalarNode('server')
                            ->info('The authorization server')
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
        $container->addCompilerPass(new IssuerDiscoveryCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
