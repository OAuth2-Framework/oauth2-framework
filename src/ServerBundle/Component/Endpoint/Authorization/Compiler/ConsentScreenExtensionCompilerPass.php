<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConsentScreenExtensionCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(ExtensionManager::class)) {
            return;
        }

        $definition = $container->getDefinition(ExtensionManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_consent_screen_extension');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
