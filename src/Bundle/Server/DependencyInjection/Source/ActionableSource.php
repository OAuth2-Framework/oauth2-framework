<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class ActionableSource extends ArraySource
{
    /**
     * {@inheritdoc}
     */
    public function load(string $path, ContainerBuilder $container, array $config)
    {
        $container->setParameter($path.'.'.$this->name().'.enabled', $config[$this->name()]['enabled']);
        if (true === $config[$this->name()]['enabled']) {
            parent::load($path, $container, $config);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
            ->end();
    }
}
