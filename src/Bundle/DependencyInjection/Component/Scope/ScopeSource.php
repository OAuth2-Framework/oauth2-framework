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
use OAuth2Framework\Component\Model\Scope\ScopeRepositoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ScopeSource implements Component
{
    /**
     * UserinfoSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new ScopePolicySource());
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'scope';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $container->setAlias(ScopeRepositoryInterface::class, $config['repository']);
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/scope'));
        $loader->load('scope.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['repository']);
                })
                ->thenInvalid('The option "repository" must be set.')
            ->end()
            ->children()
                ->scalarNode('repository')
                    ->info('Scope repository.')
                    ->defaultNull()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        //Nothing to do
        return [];
    }
}
