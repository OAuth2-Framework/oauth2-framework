<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\DependencyInjection;

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @param Component[] $components
     */
    public function __construct(
        private readonly string $alias,
        private readonly array $components
    ) {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder($this->alias);
        $rootNode = $treeBuilder->getRootNode();

        foreach ($this->components as $component) {
            $component->getNodeDefinition($rootNode, $rootNode);
        }

        return $treeBuilder;
    }
}
