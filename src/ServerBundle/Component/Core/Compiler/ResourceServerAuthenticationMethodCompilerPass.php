<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Core\Compiler;

use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMethodManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ResourceServerAuthenticationMethodCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(AuthenticationMethodManager::class)) {
            return;
        }

        $definition = $container->getDefinition(AuthenticationMethodManager::class);
        $taggedServices = $container->findTaggedServiceIds('resource_server_authentication_method');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
