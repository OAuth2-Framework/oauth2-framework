<?php

declare(strict_types=1);

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
