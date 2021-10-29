<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\ClientConfiguration\Compiler;

use OAuth2Framework\Component\ClientConfigurationEndpoint\ClientConfigurationEndpoint;
use OAuth2Framework\ServerBundle\Routing\RouteLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClientConfigurationEndpointRouteCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(ClientConfigurationEndpoint::class)) {
            return;
        }

        $path = $container->getParameter('oauth2_server.endpoint.client_configuration.path');
        $host = $container->getParameter('oauth2_server.endpoint.client_configuration.host');
        $route_loader = $container->getDefinition(RouteLoader::class);
        $route_loader->addMethodCall('addRoute', [
            'client_configuration',
            'client_configuration_endpoint_pipe',
            'handle',
            $path, // path
            [], // defaults
            [], // requirements
            [], // options
            $host, // host
            ['https'], // schemes
            ['GET', 'PUT', 'DELETE'], // methods
            '', // condition
        ]);
    }
}
