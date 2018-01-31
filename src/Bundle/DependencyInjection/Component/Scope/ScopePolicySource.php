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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Scope;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ScopePolicySource implements Component
{
    /**
     * ScopePolicySource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new ScopePolicyErrorSource());
        $this->addSubSource(new ScopePolicyDefaultSource());
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'policy';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['by_default'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/scope'));
        $loader->load('policy.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {

        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['by_default']);
                })
                ->thenInvalid('The option "repository" must be set.')
            ->end()
            ->children()
                ->scalarNode('by_default')
                    ->info('Default scope policy.')
                    ->defaultValue('none')
                ->end()
            ->end();
    }
}
