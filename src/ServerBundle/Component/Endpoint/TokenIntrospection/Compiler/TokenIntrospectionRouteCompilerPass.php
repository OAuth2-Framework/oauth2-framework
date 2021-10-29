<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\TokenIntrospection\Compiler;

use OAuth2Framework\ServerBundle\Routing\RouteLoader;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TokenIntrospectionRouteCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('token_introspection_pipe')) {
            return;
        }

        $path = $container->getParameter('oauth2_server.endpoint.token_introspection.path');
        $host = $container->getParameter('oauth2_server.endpoint.token_introspection.host');
        $route_loader = $container->getDefinition(RouteLoader::class);
        $route_loader->addMethodCall('addRoute', [
            'token_introspection_endpoint',
            'token_introspection_pipe',
            'handle',
            $path, // path
            [], // defaults
            [], // requirements
            [], // options
            $host, // host
            ['https'], // schemes
            ['POST'], // methods
            '', // condition
        ]);

        if (! $container->hasDefinition(MetadataBuilder::class)) {
            return;
        }

        $definition = $container->getDefinition(MetadataBuilder::class);
        $definition->addMethodCall(
            'addRoute',
            ['token_introspection_endpoint', 'oauth2_server_token_introspection_endpoint']
        );
    }
}
