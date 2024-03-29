<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Metadata;

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Metadata\Compiler\CustomRoutesCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomRouteSource implements Component
{
    public function name(): string
    {
        return 'custom_routes';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->setParameter(
            'oauth2_server.endpoint.metadata.custom_routes',
            $configs['endpoint']['metadata']['custom_routes']
        );
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode('custom_routes')
            ->info('Custom routes added to the metadata response.')
            ->useAttributeAsKey('name')
            ->treatNullLike([])
            ->treatFalseLike([])
            ->arrayPrototype()
            ->children()
            ->scalarNode('route_name')
            ->info('Route name.')
            ->isRequired()
            ->end()
            ->arrayNode('route_parameters')
            ->info('Parameters associated to the route (if needed).')
            ->useAttributeAsKey('name')
            ->variablePrototype()
            ->end()
            ->treatNullLike([])
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CustomRoutesCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
