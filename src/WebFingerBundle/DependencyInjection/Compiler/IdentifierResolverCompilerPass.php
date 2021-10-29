<?php

declare(strict_types=1);

namespace OAuth2Framework\WebFingerBundle\DependencyInjection\Compiler;

use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolverManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class IdentifierResolverCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(IdentifierResolverManager::class)) {
            return;
        }

        $client_manager = $container->getDefinition(IdentifierResolverManager::class);

        $taggedServices = $container->findTaggedServiceIds('webfinger_identifier_resolver');
        foreach ($taggedServices as $id => $attributes) {
            $client_manager->addMethodCall('add', [new Reference($id)]);
        }
    }
}
