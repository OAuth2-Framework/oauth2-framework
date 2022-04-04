<?php

declare(strict_types=1);

namespace OAuth2Framework\WebFingerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function __construct(
        private readonly string $alias
    ) {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder($this->alias);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('resource_repository')
            ->info('The resource repository service')
            ->isRequired()
            ->end()
            ->scalarNode('path')
            ->info(
                'The path to the issuer discovery endpoint. Should be "/.well-known/webfinger" for compliance with the RFC7033.'
            )
            ->defaultValue('/.well-known/webfinger')
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
