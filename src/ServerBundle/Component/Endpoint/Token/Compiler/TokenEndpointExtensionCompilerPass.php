<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Token\Compiler;

use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtensionManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TokenEndpointExtensionCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(TokenEndpointExtensionManager::class)) {
            return;
        }

        $definition = $container->getDefinition(TokenEndpointExtensionManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_token_endpoint_extension');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
