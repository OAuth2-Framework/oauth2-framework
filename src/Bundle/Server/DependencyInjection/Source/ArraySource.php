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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class ArraySource implements SourceInterface
{
    /**
     * @var SourceInterface[]
     */
    private $subSources = [];

    /**
     * @param string           $path
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function load(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($this->subSources as $subSource) {
            $subSource->load($path.'.'.$this->name(), $container, $config[$this->name()]);
        }
        $this->continueLoading($path.'.'.$this->name(), $container, $config[$this->name()]);
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        foreach ($this->subSources as $subSource) {
            $subSource->prepend($bundleConfig, $path.'['.$this->name().']', $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $node)
    {
        $sourceNode = $node
            ->children()
                ->arrayNode($this->name())
                    ->addDefaultsIfNotSet();
        $this->continueConfiguration($sourceNode);
        foreach ($this->subSources as $subSource) {
            $subSource->addConfiguration($sourceNode);
        }
    }

    /**
     * @param string           $path
     * @param ContainerBuilder $container
     * @param array            $config
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
    }

    /**
     * @return string
     */
    abstract protected function name(): string;

    /**
     * @param NodeDefinition $node
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        $node->addDefaultsIfNotSet();
    }

    /**
     * @param SourceInterface $source
     */
    protected function addSubSource(SourceInterface $source)
    {
        $this->subSources[] = $source;
    }
}
