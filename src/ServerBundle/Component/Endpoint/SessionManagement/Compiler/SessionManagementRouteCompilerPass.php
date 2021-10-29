<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\SessionManagement\Compiler;

use OAuth2Framework\ServerBundle\Routing\RouteLoader;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SessionManagementRouteCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(
            'oauth2_server.endpoint.session_management_pipe'
        ) || $container->getParameter('oauth2_server.endpoint.session_management.enabled') !== true) {
            return;
        }

        $path = $container->getParameter('oauth2_server.endpoint.session_management.path');
        $host = $container->getParameter('oauth2_server.endpoint.session_management.host');
        $route_loader = $container->getDefinition(RouteLoader::class);
        $route_loader->addMethodCall('addRoute', [
            'openid_connect_iframe_endpoint',
            'oauth2_server.endpoint.session_management_pipe',
            'handle',
            $path, // path
            [], // defaults
            [], // requirements
            [], // options
            $host, // host
            ['https'], // schemes
            ['GET'], // methods
            '', // condition
        ]);

        if (! $container->hasDefinition(MetadataBuilder::class)) {
            return;
        }
        $medata = $container->getDefinition(MetadataBuilder::class);
        $medata->addMethodCall('addRoute', ['check_session_iframe', 'oauth2_server_openid_connect_iframe_endpoint']);
    }
}
