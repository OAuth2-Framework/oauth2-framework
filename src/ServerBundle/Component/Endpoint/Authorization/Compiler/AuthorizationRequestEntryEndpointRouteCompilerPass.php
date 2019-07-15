<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\ServerBundle\Routing\RouteLoader;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AuthorizationRequestEntryEndpointRouteCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('authorization_request_entry_endpoint_pipe')) {
            return;
        }

        $host = $container->getParameter('oauth2_server.endpoint.authorization.host');
        $route_loader = $container->getDefinition(RouteLoader::class);

        $path = $container->getParameter('oauth2_server.endpoint.authorization.authorization_request_entry_endpoint_path');
        $route_loader->addMethodCall('addRoute', ['authorization_request_entry_endpoint', 'authorization_request_entry_endpoint_pipe', 'dispatch', $path, [], [], [], $host, ['https'], ['GET'], '']);

        if (!$container->hasDefinition(MetadataBuilder::class)) {
            return;
        }
        $definition = $container->getDefinition(MetadataBuilder::class);
        $definition->addMethodCall('addRoute', ['authorization_request_entry_endpoint', 'oauth2_server_authorization_request_entry_endpoint']);
    }
}
