<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\DependencyInjection;

use OAuth2Framework\Bundle\Server\DependencyInjection\Source\SourceInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @var SourceInterface[]
     */
    private $sourceMap;

    /**
     * @var string
     */
    private $alias;

    /**
     * Configuration constructor.
     *
     * @param string $alias
     * @param array  $sourceMap
     */
    public function __construct(string $alias, array $sourceMap)
    {
        $this->alias = $alias;
        $this->sourceMap = $sourceMap;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->alias);

        $this->buildFromSources($this->sourceMap, $rootNode);

        return $treeBuilder;
    }

    /**
     * @param array               $sources
     * @param ArrayNodeDefinition $node
     */
    private function buildFromSources(array $sources, ArrayNodeDefinition $node)
    {
        foreach ($sources as $k => $source) {
            if ($source instanceof SourceInterface) {
                $source->addConfiguration($node);
            } elseif (is_string($k) && is_array($source)) {
                $childNode = $node->children()->arrayNode($k);
                $this->buildFromSources($source, $childNode);
            }
        }
    }
}
