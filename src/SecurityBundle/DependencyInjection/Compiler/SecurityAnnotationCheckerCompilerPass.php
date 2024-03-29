<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\DependencyInjection\Compiler;

use OAuth2Framework\SecurityBundle\Annotation\AnnotationDriver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class SecurityAnnotationCheckerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(AnnotationDriver::class)) {
            return;
        }

        $definition = $container->getDefinition(AnnotationDriver::class);
        $taggedServices = $container->findTaggedServiceIds('oauth2_security_annotation_checker');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
