<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler;

use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\ClaimManager;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClaimsSupportedMetadataCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(MetadataBuilder::class) || ! $container->hasDefinition(ClaimManager::class)) {
            return;
        }
        $metadata = $container->getDefinition(MetadataBuilder::class);

        $metadata->addMethodCall('setClaimsSupported', [new Reference(ClaimManager::class)]);
        $metadata->addMethodCall('addKeyValuePair', ['claim_types_supported', ['normal', 'aggregated', 'distributed']]);
        $metadata->addMethodCall('addKeyValuePair', ['claims_parameter_supported', true]);
    }
}
