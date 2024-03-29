<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Token\Compiler;

use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GrantTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(GrantTypeManager::class)) {
            return;
        }

        $definition = $container->getDefinition(GrantTypeManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_grant_type');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }

        if ($container->hasDefinition(MetadataBuilder::class)) {
            $metadata = $container->getDefinition(MetadataBuilder::class);
            $metadata->addMethodCall('setGrantTypeManager', [new Reference(GrantTypeManager::class)]);
        }
    }
}
