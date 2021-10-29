<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TemplatePathCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('twig.loader.filesystem')) {
            return;
        }

        $loader = $container->getDefinition('twig.loader.filesystem');
        $loader->addMethodCall('addPath', [__DIR__ . '/../../../../Resources/views', 'OAuth2FrameworkServerBundle']);
    }
}
