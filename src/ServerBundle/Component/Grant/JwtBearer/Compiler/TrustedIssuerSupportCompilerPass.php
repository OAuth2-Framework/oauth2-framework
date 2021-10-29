<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Grant\JwtBearer\Compiler;

use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuerRepository;
use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TrustedIssuerSupportCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(JwtBearerGrantType::class) || ! $container->hasAlias(
            TrustedIssuerRepository::class
        )) {
            return;
        }

        $definition = $container->getDefinition(JwtBearerGrantType::class);
        $definition->addMethodCall('enableTrustedIssuerSupport', [new Reference(TrustedIssuerRepository::class)]);
    }
}
