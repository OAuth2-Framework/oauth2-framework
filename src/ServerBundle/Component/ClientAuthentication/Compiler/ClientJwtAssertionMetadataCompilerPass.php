<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\ClientAuthentication\Compiler;

use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientJwtAssertionMetadataCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(MetadataBuilder::class) || ! $container->hasDefinition(
            ClientAssertionJwt::class
        )) {
            return;
        }
        $metadata = $container->getDefinition(MetadataBuilder::class);
        $metadata->addMethodCall('setClientAssertionJwt', [new Reference(ClientAssertionJwt::class)]);
    }
}
