<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\JwksUri;

use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class JwksUriEndpointRouteCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(MetadataBuilder::class) || ! $container->has(
            'jose.key_set.oauth2_server.endpoint.jwks_uri'
        )) {
            return;
        }

        $routeName = 'jwkset_jose.controller.oauth2_server.endpoint.jwks_uri';
        $definition = $container->getDefinition(MetadataBuilder::class);
        $definition->addMethodCall('addRoute', ['jwks_uri', $routeName]);
    }
}
