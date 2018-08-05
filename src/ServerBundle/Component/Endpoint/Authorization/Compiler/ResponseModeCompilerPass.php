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

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ResponseModeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(ResponseModeManager::class)) {
            return;
        }

        $definition = $container->getDefinition(ResponseModeManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_response_mode');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }

        if ($container->hasDefinition(MetadataBuilder::class)) {
            $metadata = $container->getDefinition(MetadataBuilder::class);
            $metadata->addMethodCall('setResponseModeManager', [new Reference(ResponseModeManager::class)]);
        }
    }
}
