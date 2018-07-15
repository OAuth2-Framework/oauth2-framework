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

namespace OAuth2Framework\ServerBundle\Component\Grant\Implicit;

use OAuth2Framework\Component\ImplicitGrant\ImplicitGrantType;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ImplicitSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'implicit';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!\class_exists(ImplicitGrantType::class) || !$configs['grant']['implicit']['enabled']) {
            return;
        }

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('implicit.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        if (!\class_exists(ImplicitGrantType::class)) {
            return;
        }

        $node->children()
            ->arrayNode('implicit')
            ->canBeEnabled()
            ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
