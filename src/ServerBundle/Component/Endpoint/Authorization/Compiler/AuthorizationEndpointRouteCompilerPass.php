<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\ServerBundle\Routing\RouteLoader;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AuthorizationEndpointRouteCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('authorization_endpoint_pipe')) {
            return;
        }

        $host = $container->getParameter('oauth2_server.endpoint.authorization.host');
        $route_loader = $container->getDefinition(RouteLoader::class);

        $path = $container->getParameter('oauth2_server.endpoint.authorization.authorization_endpoint_path');
        $route_loader->addMethodCall('addRoute', ['authorization_endpoint', 'authorization_endpoint_pipe', 'dispatch', $path, [], [], [], $host, ['https'], ['GET'], '']);

        $path = $container->getParameter('oauth2_server.endpoint.authorization.consent_endpoint_path');
        $route_loader->addMethodCall('addRoute', ['consent_endpoint', 'consent_endpoint_pipe', 'dispatch', $path, [], [], [], $host, ['https'], ['GET', 'POST'], '']);

        $path = $container->getParameter('oauth2_server.endpoint.authorization.select_account_endpoint_path');
        $route_loader->addMethodCall('addRoute', ['select_account_endpoint', 'select_account_endpoint_pipe', 'dispatch', $path, [], [], [], $host, ['https'], ['GET', 'POST'], '']);

        $path = $container->getParameter('oauth2_server.endpoint.authorization.process_endpoint_path');
        $route_loader->addMethodCall('addRoute', ['process_endpoint', 'process_endpoint_pipe', 'dispatch', $path, [], [], [], $host, ['https'], ['GET', 'POST'], '']);

        if (!$container->hasDefinition(MetadataBuilder::class)) {
            return;
        }
        $definition = $container->getDefinition(MetadataBuilder::class);
        $definition->addMethodCall('addRoute', ['authorization_endpoint', 'oauth2_server_authorization_endpoint']);
    }
}
