<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Scope\Compiler;

use OAuth2Framework\Component\Scope\ScopeRepository;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ScopeMetadataCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(MetadataBuilder::class) || ! $container->hasAlias(ScopeRepository::class)) {
            return;
        }
        $metadata = $container->getDefinition(MetadataBuilder::class);
        $metadata->addMethodCall('setScopeRepository', [new Reference(ScopeRepository::class)]);
    }
}
