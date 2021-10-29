<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler;

use Jose\Component\KeyManagement\JKUFactory;
use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class JkuSupportForIdTokenBuilderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(JKUFactory::class) || ! $container->hasDefinition(
            IdTokenBuilderFactory::class
        )) {
            return;
        }

        $definition = $container->getDefinition(IdTokenBuilderFactory::class);
        $definition->addMethodCall('enableJkuSupport', [new Reference(JKUFactory::class)]);
    }
}
