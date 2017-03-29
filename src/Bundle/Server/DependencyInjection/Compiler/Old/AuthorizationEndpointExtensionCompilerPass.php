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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AuthorizationEndpointExtensionCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.authorization_endpoint.controller')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.authorization_endpoint.controller');

        $taggedServices = $container->findTaggedServiceIds('oauth2_server.authorization_endpoint.extension');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addExtension', [new Reference($id)]);
        }
    }
}
