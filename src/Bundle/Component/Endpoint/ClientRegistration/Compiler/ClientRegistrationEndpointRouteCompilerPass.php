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

namespace OAuth2Framework\Bundle\DependencyInjection\Compiler;

use OAuth2Framework\Bundle\Routing\RouteLoader;
use OAuth2Framework\Bundle\Service\MetadataBuilder;
use OAuth2Framework\Component\ClientRegistrationEndpoint\ClientRegistrationEndpoint;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClientRegistrationEndpointRouteCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(ClientRegistrationEndpoint::class)) {
            return;
        }

        $path = $container->getParameter('oauth2_server.endpoint.client_registration.path');
        $route_loader = $container->getDefinition(RouteLoader::class);
        $route_loader->addMethodCall('addRoute', [
            'client_registration',
            'client_registration_endpoint_pipe',
            'dispatch',
            $path, // path
            [], // defaults
            [], // requirements
            [], // options
            '', // host
            ['https'], // schemes
            ['POST'], // methods
            '', // condition
        ]);

        if (!$container->hasDefinition(MetadataBuilder::class)) {
            return;
        }
        $definition = $container->getDefinition(MetadataBuilder::class);
        $definition->addMethodCall('addRoute', ['registration_endpoint', 'oauth2_server_client_registration']);
    }
}
