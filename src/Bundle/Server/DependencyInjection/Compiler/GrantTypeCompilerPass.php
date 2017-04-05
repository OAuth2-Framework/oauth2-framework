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

use OAuth2Framework\Component\Server\GrantType\GrantTypeManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GrantTypeCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(GrantTypeManager::class)) {
            return;
        }

        $definition = $container->getDefinition(GrantTypeManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_grant_type');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }

        /*if ($container->hasDefinition('oauth2_server.openid_connect.metadata')) {
            $metadata = $container->getDefinition('oauth2_server.openid_connect.metadata');
            $metadata->addMethodCall('setGrantTypeManager', [new Reference(GrantTypeManager::class)]);
        }*/
    }
}
