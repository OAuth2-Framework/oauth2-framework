<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AuthorizationRequestMetadataCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(MetadataBuilder::class) || ! $container->hasDefinition(
            AuthorizationRequestLoader::class
        )) {
            return;
        }

        $metadata = $container->getDefinition(MetadataBuilder::class);
        $metadata->addMethodCall('setAuthorizationRequestLoader', [new Reference(AuthorizationRequestLoader::class)]);
    }
}
