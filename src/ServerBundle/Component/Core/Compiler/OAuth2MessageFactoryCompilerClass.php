<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Core\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class OAuth2MessageFactoryCompilerClass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->processForTaggedServices(
            $container,
            'oauth2_server.message_factory_manager.for_client_authentication',
            'oauth2_server_message_factory_for_client_authentication'
        );
        $this->processForTaggedServices(
            $container,
            'oauth2_server.message_factory_manager.for_token_authentication',
            'oauth2_server_message_factory_for_token_authentication'
        );
    }

    private function processForTaggedServices(ContainerBuilder $container, string $definition, string $tag): void
    {
        if (! $container->hasDefinition($definition)) {
            return;
        }

        $client_manager = $container->getDefinition($definition);
        $taggedServices = $container->findTaggedServiceIds($tag);
        foreach ($taggedServices as $id => $attributes) {
            $client_manager->addMethodCall('addFactory', [new Reference($id)]);
        }
    }
}
