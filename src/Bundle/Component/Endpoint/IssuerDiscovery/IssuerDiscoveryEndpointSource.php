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
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IdentifierResolver\IdentifierResolver;
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
        $container->registerForAutoconfiguration(IdentifierResolver::class)->addTag('oauth2_server_identifier_resolver');
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
                            ->info('The host of the server (e.g. example.com, my-service.net)')
                            ->isRequired()
                        ->end()
                        ->scalarNode('path')
                            ->info('The path to the issuer discovery endpoint. Should be "/.well-known/webfinger" for compliance with the specification.')
                            ->defaultValue('/.well-known/webfinger')
                        ->end()
                        ->scalarNode('resource_repository')
                            ->info('The resource repository service associated to the issuer discovery')
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
        $container->addCompilerPass(new IdentifierResolverCompilerPass());
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
