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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Compiler;

use OAuth2Framework\Component\Server\TokenIntrospectionEndpointAuthMethod\TokenIntrospectionEndpointAuthMethodManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TokenIntrospectionEndpointAuthMethodCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(TokenIntrospectionEndpointAuthMethodManager::class)) {
            return;
        }

        $definition = $container->getDefinition(TokenIntrospectionEndpointAuthMethodManager::class);
        $taggedServices = $container->findTaggedServiceIds('token_introspection_endpoint_auth_method');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addTokenIntrospectionEndpointAuthMethod', [new Reference($id)]);
        }
    }
}
