<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\ServerBundle\Routing\RouteLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AuthorizationEndpointRouteCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has('authorization_endpoint_pipe')) {
            return;
        }

        $host = $container->getParameter('oauth2_server.endpoint.authorization.host');
        $route_loader = $container->getDefinition(RouteLoader::class);

        $path = $container->getParameter('oauth2_server.endpoint.authorization.authorization_endpoint_path');
        $route_loader->addMethodCall(
            'addRoute',
            ['authorization_endpoint', 'authorization_endpoint_pipe', 'handle', $path, [], [], [], $host, ['https'], [
                'GET',
            ], '']
        );
    }
}
