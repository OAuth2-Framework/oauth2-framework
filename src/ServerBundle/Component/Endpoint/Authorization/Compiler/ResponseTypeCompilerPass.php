<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ResponseTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(ResponseTypeManager::class)) {
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
