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

namespace OAuth2Framework\ServerBundle\DependencyInjection\Compiler;

use OAuth2Framework\Component\AuthorizationEndpoint\UserAccountDiscovery\UserAccountDiscoveryManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UserAccountDiscoveryCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(UserAccountDiscoveryManager::class)) {
            return;
        }

        $definition = $container->getDefinition(UserAccountDiscoveryManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_user_account_discovery');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
