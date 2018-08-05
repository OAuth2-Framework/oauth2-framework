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

namespace OAuth2Framework\ServerBundle\Component\Core\Compiler;

use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class OAuth2MessageExtensionCompilerClass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(OAuth2MessageFactoryManager::class)) {
            return;
        }

        $client_manager = $container->getDefinition(OAuth2MessageFactoryManager::class);
        $taggedServices = $container->findTaggedServiceIds('oauth2_message_extension');
        foreach ($taggedServices as $id => $attributes) {
            $client_manager->addMethodCall('addExtension', [new Reference($id)]);
        }
    }
}
