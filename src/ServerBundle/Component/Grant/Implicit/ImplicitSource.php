<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
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
    public function name(): string
    {
        return 'implicit';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (!\class_exists(ImplicitGrantType::class) || !$configs['grant']['implicit']['enabled']) {
            return;
        }

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('implicit.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
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

    public function build(ContainerBuilder $container): void
    {
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
