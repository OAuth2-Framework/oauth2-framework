<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\DependencyInjection;

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @var Component[]
     */
    private array $components;

    private string $alias;

    /**
     * @param Component[] $components
     */
    public function __construct(string $alias, array $components)
    {
        $this->alias = $alias;
        $this->components = $components;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder($this->alias);
        $rootNode = $treeBuilder->getRootNode();

        foreach ($this->components as $component) {
            $component->getNodeDefinition($rootNode, $rootNode);
        }

        return $treeBuilder;
    }
}
