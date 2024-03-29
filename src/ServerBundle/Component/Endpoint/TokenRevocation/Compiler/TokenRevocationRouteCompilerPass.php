<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\TokenRevocation\Compiler;

use OAuth2Framework\ServerBundle\Routing\RouteLoader;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TokenRevocationRouteCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('token_revocation_endpoint_pipe')) {
            return;
        }

        $path = $container->getParameter('oauth2_server.endpoint.token_revocation.path');
        $host = $container->getParameter('oauth2_server.endpoint.token_revocation.host');
        $route_loader = $container->getDefinition(RouteLoader::class);
        $route_loader->addMethodCall('addRoute', [
            'token_revocation_endpoint',
            'token_revocation_endpoint_pipe',
            'handle',
            $path, // path
            [], // defaults
            [], // requirements
            [], // options
            $host, // host
            ['https'], // schemes
            ['GET', 'POST'], // methods
            '', // condition
        ]);

        if (! $container->hasDefinition(MetadataBuilder::class)) {
            return;
        }

        $definition = $container->getDefinition(MetadataBuilder::class);
        $definition->addMethodCall(
            'addRoute',
            ['token_revocation_endpoint', 'oauth2_server_token_revocation_endpoint']
        );
    }
}
