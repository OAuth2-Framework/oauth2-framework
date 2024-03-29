<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Metadata\Compiler;

use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomRoutesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(MetadataBuilder::class)) {
            return;
        }

        $definition = $container->getDefinition(MetadataBuilder::class);
        $customRoutes = $container->getParameter('oauth2_server.endpoint.metadata.custom_routes');
        foreach ($customRoutes as $key => $parameters) {
            $definition->addMethodCall('addRoute', [$key, $parameters['route_name'], $parameters['route_parameters']]);
        }
    }
}
