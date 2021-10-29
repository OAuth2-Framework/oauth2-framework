<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\ClientAuthentication\Compiler;

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientAuthenticationMethodCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(AuthenticationMethodManager::class)) {
            return;
        }

        $definition = $container->getDefinition(AuthenticationMethodManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_client_authentication');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }

        // Metadata
        if (! $container->hasDefinition(MetadataBuilder::class)) {
            return;
        }

        $metadata = $container->getDefinition(MetadataBuilder::class);
        $metadata->addMethodCall(
            'setTokenEndpointAuthMethodManager',
            [new Reference(AuthenticationMethodManager::class)]
        );
    }
}
