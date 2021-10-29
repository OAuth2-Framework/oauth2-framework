<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\ClientAuthentication\Compiler;

use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuerRepository;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientAssertionTrustedIssuerSupportCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(ClientAssertionJwt::class) || ! $container->hasAlias(
            TrustedIssuerRepository::class
        )) {
            return;
        }

        $definition = $container->getDefinition(ClientAssertionJwt::class);
        $definition->addMethodCall('enableTrustedIssuerSupport', [new Reference(TrustedIssuerRepository::class)]);
    }
}
