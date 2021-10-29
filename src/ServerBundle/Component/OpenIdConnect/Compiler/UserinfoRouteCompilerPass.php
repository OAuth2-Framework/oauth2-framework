<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler;

use OAuth2Framework\ServerBundle\Routing\RouteLoader;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UserinfoRouteCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('oauth2_server_userinfo_pipe')) {
            return;
        }

        $path = $container->getParameter('oauth2_server.openid_connect.userinfo_endpoint.path');
        $host = $container->getParameter('oauth2_server.openid_connect.userinfo_endpoint.host');
        $route_loader = $container->getDefinition(RouteLoader::class);
        $route_loader->addMethodCall('addRoute', [
            'openid_connect_userinfo_endpoint',
            'oauth2_server_userinfo_pipe',
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
        $definition->addMethodCall('addRoute', ['userinfo_endpoint', 'oauth2_server_openid_connect_userinfo_endpoint']);
    }
}
