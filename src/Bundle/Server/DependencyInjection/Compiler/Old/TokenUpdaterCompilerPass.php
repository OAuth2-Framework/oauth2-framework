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

namespace OAuth2Framework\Bundle\Server\CorePlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TokenUpdaterCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasAlias('oauth2_server.core.access_token_manager')) {
            return;
        }

        $alias = $container->getAlias('oauth2_server.core.access_token_manager');
        $definition = $container->getDefinition($alias);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server.access_token_updater');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addTokenUpdater', [new Reference($id)]);
        }
    }
}
