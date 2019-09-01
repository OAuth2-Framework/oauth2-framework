<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\Core\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class OAuth2MessageExtensionCompilerClass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->processForService($container, 'oauth2_server.message_factory_manager.for_client_authentication');
        $this->processForService($container, 'oauth2_server.message_factory_manager.for_authorization_endpoint');
        $this->processForService($container, 'oauth2_server.message_factory_manager.for_client_registration');
        $this->processForService($container, 'oauth2_server.message_factory_manager.for_client_configuration');
    }

    private function processForService(ContainerBuilder $container, string $definition): void
    {
        if ($container->hasDefinition($definition)) {
            $service = $container->getDefinition($definition);
            $taggedServices = $container->findTaggedServiceIds('oauth2_message_extension');
            foreach ($taggedServices as $id => $attributes) {
                $service->addMethodCall('addExtension', [new Reference($id)]);
            }
        }
    }
}
