<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ParameterCheckerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(ParameterCheckerManager::class)) {
            return;
        }

        $definition = $container->getDefinition(ParameterCheckerManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_authorization_parameter_checker');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
