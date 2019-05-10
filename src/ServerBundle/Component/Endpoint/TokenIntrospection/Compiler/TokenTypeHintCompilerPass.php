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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\TokenIntrospection\Compiler;

use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHintManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TokenTypeHintCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(TokenTypeHintManager::class)) {
            return;
        }

        $definition = $container->getDefinition(TokenTypeHintManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_introspection_type_hint');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
