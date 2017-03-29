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

namespace OAuth2Framework\Bundle\Server\ExceptionManagerPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExtensionCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.exception.manager')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.exception.manager');
        $taggedServices = $container->findTaggedServiceIds('oauth2_server.exception_manager.extension');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addExtension', [new Reference($id)]);
        }
    }
}
