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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ResponseTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(ResponseTypeManager::class)) {
            return;
        }

        $definition = $container->getDefinition(ResponseTypeManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_response_type');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }

        if ($container->hasDefinition(MetadataBuilder::class)) {
            $metadata = $container->getDefinition(MetadataBuilder::class);
            $metadata->addMethodCall('setResponseTypeManager', [new Reference(ResponseTypeManager::class)]);
        }
    }
}
