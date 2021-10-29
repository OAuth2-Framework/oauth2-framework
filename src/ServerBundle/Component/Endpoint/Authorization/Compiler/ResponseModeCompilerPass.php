<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ResponseModeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(ResponseModeManager::class)) {
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
