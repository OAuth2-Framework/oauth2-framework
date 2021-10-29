<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Metadata\Compiler;

use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CommonMetadataCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(MetadataBuilder::class)) {
            return;
        }

        $metadata = $container->getDefinition(MetadataBuilder::class);
        $issuer = $container->getParameter('oauth2_server.server_uri');
        $metadata->addMethodCall('addKeyValuePair', ['issuer', $issuer]);
    }
}
